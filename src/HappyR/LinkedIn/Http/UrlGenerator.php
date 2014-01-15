<?php


namespace HappyR\LinkedIn\Http;

use HappyR\LinkedIn\Storage\DataStorage;

/**
 * Class Http
 *
 * @author Tobias Nyholm
 *
 */
class UrlGenerator
{
    /**
     * Maps aliases to LinkedIn domains.
     */
    public static $domainMap = array(
        'api'         => 'https://api.linkedin.com/',
        'www'         => 'https://www.linkedin.com/',
    );

    /**
     * Indicates if we trust HTTP_X_FORWARDED_* headers.
     *
     * @var boolean
     */
    protected $trustForwarded = false;



    /**
     * Build the URL for given domain alias, path and parameters.
     *
     * @param $name string The name of the domain
     * @param $path string Optional path (without a leading slash)
     * @param $params array Optional query parameters
     *
     * @return string The URL for the given parameters
     */
    public function getUrl($name, $path='', $params=array())
    {
        $url = self::$domainMap[$name];
        if ($path) {
            if ($path[0] === '/') {
                $path = substr($path, 1);
            }
            $url .= $path;
        }

        if ($params) {
            //it needs to be PHP_QUERY_RFC3986. We want to have %20 between scopes
            $url .= '?' . http_build_query($params, null, '&', PHP_QUERY_RFC3986);
        }

        return $url;
    }

    /**
     * Returns the Current URL,
     * not persist.
     *
     * @return string The current URL
     */
    public function getCurrentUrl()
    {
        $protocol = $this->getHttpProtocol() . '://';
        $host = $this->getHttpHost();
        $currentUrl = $protocol.$host.$_SERVER['REQUEST_URI'];
        $parts = parse_url($currentUrl);

        $query = '';
        if (!empty($parts['query'])) {
            // drop known linkedin params
            $params = explode('&', $parts['query']);
            $savedParams = array();
            foreach ($params as $param) {
                if ($this->shouldRetainParam($param)) {
                    $savedParams[] = $param;
                }
            }

            if (!empty($savedParams)) {
                $query = '?'.implode($savedParams, '&');
            }
        }

        // use port if non default
        $port =
            isset($parts['port']) &&
            (($protocol === 'http://' && $parts['port'] !== 80) ||
                ($protocol === 'https://' && $parts['port'] !== 443))
                ? ':' . $parts['port'] : '';

        // rebuild
        return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }

    /**
     * Returns true if and only if the key or key/value pair should
     * be retained as part of the query string.  This amounts to
     * a brute-force search of the very small list of LinkedIn-specific
     * params that should be stripped out.
     *
     * @param string $param A key or key/value pair within a URL's query (e.g.'foo=a', 'foo=', or 'foo'.
     *
     * @return boolean
     */
    protected function shouldRetainParam($param)
    {
        foreach (DataStorage::$validKeys as $dropMe) {
            if ($param === $dropMe || strpos($param, $dropMe.'=') === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the host
     *
     *
     * @return mixed
     */
    protected function getHttpHost()
    {
        if ($this->trustForwarded && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            return $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        return $_SERVER['HTTP_HOST'];
    }

    /**
     * Get the protocol
     *
     *
     * @return string
     */
    protected function getHttpProtocol()
    {
        if ($this->trustForwarded && isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            if ($_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
                return 'https';
            }
            return 'http';
        }
        /*apache + variants specific way of checking for https*/
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] == 1)) {
            return 'https';
        }
        /*nginx way of checking for https*/
        if (isset($_SERVER['SERVER_PORT']) &&
            ($_SERVER['SERVER_PORT'] === '443')) {
            return 'https';
        }
        return 'http';
    }

    /**
     *
     * @param boolean $trustForwarded
     *
     * @return $this
     */
    public function setTrustForwarded($trustForwarded)
    {
        $this->trustForwarded = $trustForwarded;

        return $this;
    }
}