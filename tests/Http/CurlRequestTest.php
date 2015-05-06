<?php

namespace Happyr\LinkedIn\Http;

/**
 * Class RequestTest.
 *
 * @author Tobias Nyholm
 */
class CurlRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testPrepareParams()
    {
        $dummy = new Dummy();
        $result = $dummy->prepareParams('url', array(), 'GET', null);

        //make sure we include all values from $curlOptions
        $this->assertCount(0, @array_diff(CurlRequest::$curlOptions, $result));

        //test url
        $this->assertEquals('url', $result[CURLOPT_URL]);

        //we should not have any extra http headers but "Expect:"
        $this->assertCount(1, $result[CURLOPT_HTTPHEADER]);
        $this->assertEquals('Expect:', $result[CURLOPT_HTTPHEADER][0]);
    }

    public function testPrepareParamsPost()
    {
        $dummy = new Dummy();
        $result = $dummy->prepareParams('url', array('body' => array('foo' => 'bar', 'baz' => 'biz')), 'post');
        $this->assertEquals('foo=bar&baz=biz', $result[CURLOPT_POSTFIELDS]);

        $params = array('foo' => 'bar', 'baz' => 'biz');
        $result = $dummy->prepareParams('url', array('json' => $params), 'post');
        $this->assertEquals(json_encode($params), $result[CURLOPT_POSTFIELDS]);

        //make sure we don't json encode a json encoded string
        $result = $dummy->prepareParams('url', array('body' => json_encode($params)), 'post');
        $this->assertEquals(json_encode($params), $result[CURLOPT_POSTFIELDS]);

        $params = '<xml><param>1</param></xml>';
        $result = $dummy->prepareParams('url', array('body' => $params), 'post');
        $this->assertEquals($params, $result[CURLOPT_POSTFIELDS]);
    }

    public function testPrepareResponseJson()
    {
        $dummy = new Dummy();

        $body = '{foo:bar}';
        $response = $this->getTestHeaders().$body;
        $length = strlen($this->getTestHeaders());
        $options['headers']['Content-Type'] = 'application/json';

        $result = $dummy->prepareResponse($options, $response, $length);
        $this->assertEquals(json_decode($body, true), $result);
    }

    public function testPrepareResponseXml()
    {
        $dummy = new Dummy();

        $body = 'foobar';
        $response = $this->getTestHeaders().$body;
        $length = strlen($this->getTestHeaders());
        $options = array();

        $result = $dummy->prepareResponse($options, $response, $length);
        $this->assertEquals($body, $result);
    }

    public function testPrepareResponseSimpleXml()
    {
        $dummy = new Dummy();

        $body = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<person>
  <firstname>foo</firstname>
  <lastname>bar</lastname>
</person>
';
        $response = $this->getTestHeaders().$body;
        $length = strlen($this->getTestHeaders());
        $options = array('simple_xml' => true);

        $result = $dummy->prepareResponse($options, $response, $length);
        $this->assertInstanceOf('\SimpleXMLElement', $result);
        $this->assertEquals('foo', $result->firstname);
    }

    public function testGetHeadersFromLastResponse()
    {
        $dummy = new Dummy();
        $this->assertNull($dummy->getHeadersFromLastResponse());
        $dummy->setLastHeaders($this->getTestHeaders());

        $headers = $dummy->getHeadersFromLastResponse();
        $this->assertCount(7, $headers);
        $this->assertCount(2, $headers['x-li-format']);
        $this->assertEquals('Foo', $headers['Server'][0]);
    }

    /**
     * @return string
     */
    private function getTestHeaders()
    {
        return 'HTTP/1.1 200 OK
Server: Foo
Vary: *
x-li-format: json
x-li-format: json2
Content-Type: application/json;charset=UTF-8
Date: Wed, 06 May 2015 11:09:00 GMT
Connection: keep-alive
Set-Cookie: lidc="b=TB84:g=59:u=47:i=325235:t=235235:s=25235325-sfdf"; Expires=Thu, 07 May 2015 07:08:45 GMT; domain=.linkedin.com; Path=/

';
    }
}

/**
 * Class Dummy to expose protected params.
 */
class Dummy extends CurlRequest
{
    public function prepareParams($url, $options, $method)
    {
        return parent::prepareParams($url, $options, $method);
    }

    public function setLastHeaders($lastHeaders)
    {
        $this->lastHeaders = $lastHeaders;

        return $this;
    }

    public function prepareResponse(array $options, $response, $headerLength)
    {
        return parent::prepareResponse($options, $response, $headerLength);
    }
}
