<?php

namespace HappyR\LinkedIn\Http;

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
    public function send($url, $params = array(), $method = 'GET', $contentType = null);
}