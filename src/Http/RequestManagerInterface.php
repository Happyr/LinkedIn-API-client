<?php

namespace Happyr\LinkedIn\Http;

use Happyr\LinkedIn\Exception\LinkedInTransferException;
use Http\Client\HttpClient;

/**
 * A request manager builds a request.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface RequestManagerInterface
{
    /**
     * Send a request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $headers
     * @param string $body
     * @param string $protocolVersion
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws LinkedInTransferException
     */
    public function sendRequest($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1');

    /**
     * @param \Http\Client\HttpClient $httpClient
     *
     * @return RequestManager
     */
    public function setHttpClient(HttpClient $httpClient);

    /**
     * Get the last request we sent
     *
     * @return \Psr\Http\Message\RequestInterface
     */
    public function getLastRequest();


    /**
     * Get the last response we received
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getLastResponse();

}
