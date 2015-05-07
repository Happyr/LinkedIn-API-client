<?php

namespace Happyr\LinkedIn\Http;

use Happyr\LinkedIn\Exceptions\LinkedInApiException;

/**
 * Class Request.
 *
 * Makes an HTTP request with curl
 *
 * @author Tobias Nyholm
 */
class CurlRequest implements RequestInterface
{
    /**
     * @var mixed lastHeaders
     */
    protected $lastHeaders;

    /**
     * Default options for curl.
     */
    public static $curlOptions = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => RequestInterface::USER_AGENT,
        CURLOPT_VERBOSE        => true,
        CURLOPT_HEADER         => true,
    );

    /**
     * {@inheritdoc}
     *
     * This method can be overridden by subclasses if
     * developers want to do fancier things or use something other than curl to
     * make the request.
     */
    public function send($method, $url, array $options = array())
    {
        $opts = $this->prepareParams($url, $options, $method);

        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);

        if ($response === false) {
            $e = new LinkedInApiException(
                array(
                    'error_code' => curl_errno($ch),
                    'error' => array(
                        'message' => curl_error($ch),
                        'type' => 'CurlException',
                    ),
                )
            );

            curl_close($ch);
            throw $e;
        }
        $headerLength = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        return $this->prepareResponse($options, $response, $headerLength);
    }

    /**
     * {@inheritdoc}
     */
    public function getHeadersFromLastResponse()
    {
        if (empty($this->lastHeaders)) {
            return;
        }

        if (is_array($this->lastHeaders)) {
            return $this->lastHeaders;
        }

        $headers = explode("\n", trim($this->lastHeaders));
        $this->lastHeaders = array();
        // Remove HTTP status line
        array_shift($headers);
        foreach ($headers as $line) {
            list($name, $value) = explode(':', $line, 2);
            $this->lastHeaders[trim($name)][] = trim($value);
        }

        return $this->lastHeaders;
    }

    /**
     * Prepare Curl parameters.
     *
     * @param string $url
     * @param array  $options
     * @param string $method
     *
     * @return array
     */
    protected function prepareParams($url, $options, $method)
    {
        $opts = self::$curlOptions;

        if (isset($options['json'])) {
            $options['body'] = json_encode($options['json']);
        }

        $opts[CURLOPT_POST] = strtoupper($method) === 'POST';
        if ($opts[CURLOPT_POST] && isset($options['body'])) {
            $opts[CURLOPT_POSTFIELDS] = is_array($options['body']) ? http_build_query($options['body'], null, '&') : $options['body'];
        }

        $opts[CURLOPT_URL] = $url;

        // disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
        $opts[CURLOPT_HTTPHEADER] = array('Expect:');

        if (isset($options['headers'])) {
            foreach ($options['headers'] as $name => $value) {
                $opts[CURLOPT_HTTPHEADER][] = "{$name}: {$value}";
            }
        }

        return $opts;
    }

    /**
     * @param array $options
     * @param       $response
     * @param       $headerLength
     *
     * @return mixed|\SimpleXMLElement|string
     *
     * @throws LinkedInApiException
     */
    protected function prepareResponse(array $options, $response, $headerLength)
    {
        $this->lastHeaders = substr($response, 0, $headerLength);
        $body = substr($response, $headerLength);

        if (isset($options['headers']['Content-Type']) && $options['headers']['Content-Type'] === 'application/json') {
            return json_decode($body, true);
        }

        if (isset($options['simple_xml']) && $options['simple_xml']) {
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

        return $body;
    }
}
