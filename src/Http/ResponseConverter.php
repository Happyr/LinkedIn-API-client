<?php

namespace Happyr\LinkedIn\Http;

use Happyr\LinkedIn\Exception\InvalidArgumentException;
use Happyr\LinkedIn\Exception\LinkedInTransferException;
use Psr\Http\Message\ResponseInterface;

class ResponseConverter
{
    /**
     * Convert a PSR-7 response to a data type you want to work with.
     *
     * @param ResponseInterface $response
     * @param string            $requestFormat
     * @param string            $dataType
     *
     * @return ResponseInterface|\Psr\Http\Message\StreamInterface|\SimpleXMLElement|string
     *
     * @throws InvalidArgumentException
     * @throws LinkedInTransferException
     */
    public static function convert(ResponseInterface $response, $requestFormat, $dataType)
    {
        if (($requestFormat === 'json' && $dataType === 'simple_xml') ||
            ($requestFormat === 'xml' && $dataType === 'array')) {
            throw new InvalidArgumentException('Can not use reponse data format "%s" with the request format "%s".', $dataType, $requestFormat);
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
                throw new InvalidArgumentException('Format "%s" is not supported', $dataType);
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
     * @throws LinkedInTransferException
     */
    public static function convertToSimpleXml(ResponseInterface $response)
    {
        $body = $response->getBody();
        try {
            return new \SimpleXMLElement((string) $body ?: '<root />');
        } catch (\Exception $e) {
            throw new LinkedInTransferException('Unable to parse response body into XML.');
        }
    }
}
