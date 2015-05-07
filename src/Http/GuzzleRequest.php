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
        // Do we use json or simple_xml for this request?
        $json = isset($options['headers']['Content-Type']) && $options['headers']['Content-Type'] === 'application/json';
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
                        'message' => $this->parseErrorMessage($guzzleException, $json),
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

        if ($json) {
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
     * @param bool            $json
     *
     * @return string
     */
    protected function parseErrorMessage(ClientException $guzzleException, $json)
    {
        $guzzleResponse = $guzzleException->getResponse();

        if ($json) {
            $array = $guzzleResponse->json();

            return $array['message'];
        }

        return (string) $guzzleResponse->xml()->message;
    }
}
