<?php

namespace Happyr\LinkedIn\Http;

/**
 * Class RequestInterface
 *
 * The Request purpose is to send a HTTP request to the API.
 *
 * @author Tobias Nyholm
 *
 */
interface RequestInterface
{
    /**
     * Makes an HTTP request.
     *
     * @param string $url The URL to make the request to
     * @param array $params The parameters to use for the POST body
     * @param string $method
     * @param string $contentType Either json or xml or null (defaults to null)
     *
     * @return string The response text
     * @throws \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function send($url, $params = array(), $method = 'GET', $contentType = null);
}
