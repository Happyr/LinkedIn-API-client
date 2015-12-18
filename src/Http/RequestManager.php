<?php

namespace Happyr\LinkedIn\Http;

use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;

/**
 * A request manager builds a request.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RequestManager
{
    /**
     * @var \Http\Client\HttpClient
     */
    private $httpClient;

    /**
     * @param string $method
     * @param string $uri
     * @param array  $headers
     * @param null   $body
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendRequest($method, $uri, array $headers = [], $body = null)
    {
        $request = MessageFactoryDiscovery::find()->createRequest($method, $uri, $headers, $body);

        return $this->getHttpClient()->sendRequest($request);
    }

    /**
     * @param \Http\Client\HttpClient $httpClient
     *
     * @return RequestManager
     */
    public function setHttpClient(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    /**
     * @return HttpClient
     */
    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = HttpClientDiscovery::find();
        }

        return $this->httpClient;
    }
}
