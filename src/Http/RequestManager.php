<?php

namespace Happyr\LinkedIn\Http;

use Happyr\LinkedIn\Exception\LinkedInTransferException;
use Http\Client\Exception\TransferException;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Message\MessageFactory;

/**
 * A class to create HTTP requests and to send them.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class RequestManager implements RequestManagerInterface
{
    /**
     * @var \Http\Client\HttpClient
     */
    private $httpClient;

    /**
     * @var \Http\Message\MessageFactory
     */
    private $messageFactory;

    /**
     * @var \Psr\Http\Message\RequestInterface
     */
    private $lastRequest;


    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    private $lastResponse;


    /**
     * {@inheritdoc}
     */
    public function getLastRequest() {
        return $this->lastRequest;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastResponse() {
        return $this->lastResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function sendRequest($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1')
    {
        $request = $this->getMessageFactory()->createRequest($method, $uri, $headers, $body, $protocolVersion);

        $this->lastRequest = $request;

        try {
            $response = $this->getHttpClient()->sendRequest($request);
            $this->lastResponse = $response;
            return $response;
        } catch (TransferException $e) {
            throw new LinkedInTransferException('Error while requesting data from LinkedIn.com: '.$e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
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

    /**
     * @param MessageFactory $messageFactory
     *
     * @return RequestManager
     */
    public function setMessageFactory(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;

        return $this;
    }

    /**
     * @return \Http\Message\MessageFactory
     */
    private function getMessageFactory()
    {
        if ($this->messageFactory === null) {
            $this->messageFactory = MessageFactoryDiscovery::find();
        }

        return $this->messageFactory;
    }
}
