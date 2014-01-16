<?php


namespace HappyR\LinkedIn\Http;

use HappyR\LinkedIn\Exceptions\LinkedInApiException;

/**
 * Class Http
 *
 * @author Tobias Nyholm
 *
 */
class Request implements RequestInterface
{
    /**
     * Default options for curl.
     */
    public static $curlOptions = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'linkedin-php-client',
    );

    /**
     * Makes an HTTP request. This method can be overridden by subclasses if
     * developers want to do fancier things or use something other than curl to
     * make the request.
     *
     * @param string $url The URL to make the request to
     * @param array $params The parameters to use for the POST body
     * @param CurlHandler $ch Initialized curl handle
     *
     * @return string The response text
     */
    public function send($url, $params=array(), $method='GET', $ch=null)
    {
        if (!$ch) {
            $ch = curl_init();
        }

        $opts = self::$curlOptions;
        $opts[CURLOPT_POST] = strtoupper($method)=='POST';
        if ($opts[CURLOPT_POST]) {
            $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
        }

        $opts[CURLOPT_URL] = $url;

        // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
        if (isset($opts[CURLOPT_HTTPHEADER])) {
            $existingHeaders = $opts[CURLOPT_HTTPHEADER];
            $existingHeaders[] = 'Expect:';
            $opts[CURLOPT_HTTPHEADER] = $existingHeaders;
        } else {
            $opts[CURLOPT_HTTPHEADER] = array('Expect:');
        }

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $errno = curl_errno($ch);

        if ($result === false) {
            $e = new LinkedInApiException(
                array(
                    'error_code' => curl_errno($ch),
                    'error' => array(
                        'message' => curl_error($ch),
                        'type' => 'CurlException',
                    ),
                )
            );

            curl_close($ch);
            throw $e;
        }

        curl_close($ch);

        return $result;
    }
}