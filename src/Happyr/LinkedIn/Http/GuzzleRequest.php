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
    public function send($method, $url, array $options = array())
    {
        $client = $this->getClient();
        $request = $client->createRequest($method, $url, $options);

        // Do we use json or simple_xml for this request?
        $json = $options['headers']['Content-Type']==='application/json';
        $xml=false;
        if (isset($options['simple_xml'])) {
            $xml = (bool) $options['simple_xml'];
            unset($options['simple_xml']);
        }

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

        if ($json) {
            return $response->json();
        }

        if ($xml) {
            return $response->xml();
        }

        return (string) $response->getBody();
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
     * @param bool $json
     *
     * @return string
     */
    private function parseErrorMessage(ClientException $guzzleException, $json)
    {
        $guzzleResponse = $guzzleException->getResponse();

        if ($json) {
            $array = $guzzleResponse->json();

            return $array['message'];
        }

        return (string) $guzzleResponse->xml()->message;

    }
}
