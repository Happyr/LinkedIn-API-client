<?php

namespace Happyr\LinkedIn\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\TransferException;
use Happyr\LinkedIn\Exceptions\LinkedInApiException;

/**
 * @author Tobias Nyholm
 */
class GuzzleRequest implements RequestInterface
{
    /**
     * @var array lastHeaders
     */
    private $lastHeaders;

    /**
     * {@inheritdoc}
     */
    public function send($method, $url, array $options = array())
    {
        // Do we use simple_xml for this request?
        $simpleXml = false;
        if (isset($options['simple_xml'])) {
            $simpleXml = (bool) $options['simple_xml'];
            unset($options['simple_xml']);
        }

        $client = $this->getClient();
        $request = $client->createRequest($method, $url, $options);

        try {
            $response = $client->send($request);
        } catch (ClientException $guzzleException) {
            $e = new LinkedInApiException(
                array(
                    'error_code' => $guzzleException->getCode(),
                    'error' => array(
                        'message' => $this->parseErrorMessage($guzzleException),
                        'type' => 'GuzzleException',
                    ),
                )
            );

            throw $e;
        } catch (TransferException $guzzleException) {
            $e = new LinkedInApiException(
                array(
                    'error_code' => $guzzleException->getCode(),
                    'error' => array(
                        'message' => $guzzleException->getMessage(),
                        'type' => 'GuzzleException',
                    ),
                )
            );

            throw $e;
        }

        $this->lastHeaders = $response->getHeaders();

        if ($this->isJsonResponse($response)) {
            return $response->json();
        }

        if ($simpleXml) {
            return $response->xml();
        }

        return (string) $response->getBody();
    }

    /**
     * {@inheritdoc}
     */
    public function getHeadersFromLastResponse()
    {
        return $this->lastHeaders;
    }

    /**
     * @return Client
     */
    protected function getClient()
    {
        return new Client(array(
            'User-Agent' => RequestInterface::USER_AGENT,
        ));
    }

    /**
     * Parse an exception and return its body's error message.
     *
     * @param ClientException $guzzleException
     *
     * @return string
     */
    protected function parseErrorMessage(ClientException $guzzleException)
    {
        $response = $guzzleException->getResponse();

        if ($this->isJsonResponse($response)) {
            $array = $response->json();

            if (isset($array['message'])) {
                return $array['message'];
            };

            if (isset($array['error_description'])) {
                return $array['error_description'];
            };
        }

        return (string) $response->xml()->message;
    }

    /**
     * @param $guzzleResponse
     *
     * @return bool
     */
    protected function isJsonResponse($guzzleResponse)
    {
        return false !== strstr($guzzleResponse->getHeader('Content-Type'), 'application/json');
    }
}
