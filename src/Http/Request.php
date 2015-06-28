<?php

namespace Happyr\LinkedIn\Http;

/**
 * Class Request implements RequestInterface.
 *
 * The Request purpose is to send a HTTP request to the API.
 *
 * @author Tobias Nyholm
 */
class Request implements RequestInterface
{
    const USER_AGENT = 'Happyr/0.5';

    /**
     * Makes an HTTP request.
     *
     * @param string $method  HTTP method
     * @param string $url     The URL to make the request to
     * @param array  $options with all the options related to the array.
     *
     * @return string The response text
     *
     * @throws \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function send($method, $url, array $options)
    {


    }

    /**
     * @return array|null with HTTP headers. The header name is the array key. Returns null of no previous request.
     */
    public function getHeadersFromLastResponse()
    {



    }
}
