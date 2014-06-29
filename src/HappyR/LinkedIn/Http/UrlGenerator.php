<?php

namespace HappyR\LinkedIn\Http;

/**
 * Class UrlGenerator
 *
 * @author Tobias Nyholm
 *
 */
class UrlGenerator implements UrlGeneratorInterface
{
    /**
     * @var array knownLinkedInParams
     *
     * A list of params that might be in the query string
     */
    public static $knownLinkedInParams = array('state', 'code', 'access_token', 'user');

    /**
     * @var array domainMap
     *
     * Maps aliases to LinkedIn domains.
     */
    public static $domainMap = array(
        'api'         => 'https://api.linkedin.com/',
        'www'         => 'https://www.linkedin.com/',
    );

    /**
     * @var boolean
     *
     * Indicates if we trust HTTP_X_FORWARDED_* headers.
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
            // we cant run http_build_query($params, null, '&', PHP_QUERY_RFC3986); because it is not supported in php 5.3 or hhvm
            $url .= '?';
            foreach ($params as $key => $value) {
                $url .= sprintf('%s=%s&', rawurlencode($key), rawurlencode($value));
            }
            $url=rtrim($url, '&');
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
            $query=$this->dropLinkedInParams($parts['query']);
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
     * Drop known LinkedIn params. Ie those in self::$knownLinkeInParams
     *
     * @param string $query
     *
     * @return string query without LinkedIn params. This string is prepended with a question mark '?'
     */
    protected function dropLinkedInParams($query)
    {
        if ($query=='') {
            return '';
        }

        $params = explode('&', $query);
        foreach ($params as $i => $param) {
            /*
             * A key or key/value pair might me 'foo=bar', 'foo=', or 'foo'.
             */
            //get the first value of the array you will get when you explode()
            list($key)=explode('=', $param, 2);
            if (in_array($key, self::$knownLinkedInParams)) {
                unset($params[$i]);
            }
        }

        //assert: params is an array. It might be empty
        if (!empty($params)) {
            return '?'.implode($params, '&');
        }

        return '';
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