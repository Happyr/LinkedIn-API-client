<?php

namespace Happyr\LinkedIn\Http;

use GuzzleHttp\Psr7\Response;

class ResponseConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $body = '{"foo":"bar"}';
        $response = new Response(200, [], $body);

        $result = ResponseConverter::convert($response, 'json', 'psr7');
        $this->assertInstanceOf('Psr\Http\Message\ResponseInterface', $result);

        $result = ResponseConverter::convert($response, 'json', 'stream');
        $this->assertInstanceOf('Psr\Http\Message\StreamInterface', $result);

        $result = ResponseConverter::convert($response, 'json', 'string');
        $this->assertTrue(is_string($result));
        $this->assertEquals($body, $result);

        $result = ResponseConverter::convert($response, 'json', 'array');
        $this->assertTrue(is_array($result));

        $body = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<person>
  <firstname>foo</firstname>
  <lastname>bar</lastname>
</person>
';
        $response = new Response(200, [], $body);
        $result = ResponseConverter::convert($response, 'xml', 'simple_xml');
        $this->assertInstanceOf('\SimpleXMLElement', $result);
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exception\InvalidArgumentException
     */
    public function testConvertJsonToSimpleXml()
    {
        $body = '{"foo":"bar"}';
        $response = new Response(200, [], $body);

        ResponseConverter::convert($response, 'json', 'simple_xml');
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exception\InvalidArgumentException
     */
    public function testConvertXmlToArray()
    {
        $body = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<person>
  <firstname>foo</firstname>
  <lastname>bar</lastname>
</person>
';
        $response = new Response(200, [], $body);

        ResponseConverter::convert($response, 'xml', 'array');
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exception\InvalidArgumentException
     */
    public function testConvertJsonToFoobar()
    {
        $body = '{"foo":"bar"}';
        $response = new Response(200, [], $body);

        ResponseConverter::convert($response, 'json', 'foobar');
    }

    public function testConvertToSimpleXml()
    {
        $body = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<person>
  <firstname>foo</firstname>
  <lastname>bar</lastname>
</person>
';

        $response = new Response(200, [], $body);
        $result = ResponseConverter::convertToSimpleXml($response);

        $this->assertInstanceOf('\SimpleXMLElement', $result);
        $this->assertEquals('foo', $result->firstname);
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exception\LinkedInTransferException
     */
    public function testConvertToSimpleXmlError()
    {
        $body = '{Foo: bar}';

        $response = new Response(200, [], $body);
        $result = ResponseConverter::convertToSimpleXml($response);

        $this->assertInstanceOf('\SimpleXMLElement', $result);
        $this->assertEquals('foo', $result->firstname);
    }
}
