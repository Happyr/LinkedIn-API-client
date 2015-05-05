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
     * {@inheritdoc}
     */
    public function send($url, $params = array(), $method = 'GET', $contentType = null)
    {

        $client = $this->getClient();
        $options = $this->createOptions($params, $method, $contentType);
        $request = $client->createRequest($method, $url, $options);

        try {
            $response = $client->send($request);
        } catch (ClientException $guzzleException) {
            $contentType = $contentType===null?'xml':$contentType;
            $e = new LinkedInApiException(
                array(
                    'error_code' => $guzzleException->getCode(),
                    'error' => array(
                        'message' => $this->parseErrorMessage($guzzleException, $contentType),
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

        return (string) $response->getBody();
    }

    /**
     * Create options for Guzzle request
     *
     * @param mixed $params
     * @param string $method
     * @param string $contentType
     *
     * @return array
     */
    protected function createOptions($params, $method, $contentType)
    {
        if (strtoupper($method) == 'POST') {
            if ($contentType == 'json') {
                $options = array('json' => $params);
            } elseif ($contentType == 'xml') {
                $options = array('body' => is_string($params) ? $params : $params->asXML());
            } else {
                $options = array('body' => $params);
            }
        }

        if ($contentType) {
            $options['headers']['Content-Type'] = $contentType == 'xml' ? 'text/xml' : 'application/json';
        }

        $this->modifyOptions($options);

        return $options;
    }


    /**
     * Override this function if you modify the options
     *
     * @param $options
     */
    protected function modifyOptions(&$options)
    {
    }

    /**
     *
     * @return Client
     */
    protected function getClient()
    {
        return new Client(array(
            'User-Agent' => 'linkedin-php-client',
        ));
    }

    /**
     * Parse an exception and return its body's error message.
     *
     * @param ClientException $guzzleException
     * @return string
     */
    private function parseErrorMessage(ClientException $guzzleException, $contentType)
    {
        $guzzleResponse = $guzzleException->getResponse();

        if ($contentType === 'json') {
            $array = $guzzleResponse->json();

            return $array['message'];
        }

        return (string) $guzzleResponse->xml()->message;

    }
}
