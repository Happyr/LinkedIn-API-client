<?php

namespace Happyr\LinkedIn\Http;

use Happyr\LinkedIn\Exceptions\LinkedInApiException;
use Psr\Http\Message\ResponseInterface;

class ResponseConverter
{
    /**
     * @param ResponseInterface $response
     * @param string            $format
     *
     * @return ResponseInterface|\Psr\Http\Message\StreamInterface|\SimpleXMLElement|string
     *
     * @throws LinkedInApiException
     */
    public static function convert(ResponseInterface $response, $requestFormat, $dataType)
    {
        if (($requestFormat === 'json' && $dataType === 'simple_xml') ||
            ($requestFormat === 'xml' && $dataType === 'array')) {
            throw new \InvalidArgumentException(sprintf('Can not use reponse data format "%s" with the request format "s%"', $dataType, $requestFormat));
        }

        switch ($dataType) {
            case 'array':
                return self::convertToArray($response);
            case 'string':
                return $response->getBody()->__toString();
            case 'simple_xml':
                return self::convertToSimpleXml($response);
            case 'stream':
                return $response->getBody();
            case 'psr7':
                return $response;
            default:
                throw new \InvalidArgumentException(sprintf('Format "%s" is not supported', $dataType));
        }
    }

    /**
     * @param ResponseInterface $response
     *
     * @return string
     */
    public static function convertToArray(ResponseInterface $response)
    {
        return json_decode($response->getBody(), true);
    }

    /**
     * @param ResponseInterface $response
     *
     * @return \SimpleXMLElement
     *
     * @throws LinkedInApiException
     */
    public static function convertToSimpleXml(ResponseInterface $response)
    {
        $body = $response->getBody();
        try {
            return new \SimpleXMLElement((string) $body ?: '<root />');
        } catch (\Exception $e) {
            throw new LinkedInApiException(
                array(
                    'error' => array(
                        'message' => 'Unable to parse response body into XML: '.$e->getMessage(),
                        'type' => 'XmlParseException',
                    ),
                )
            );
        }
    }
}
