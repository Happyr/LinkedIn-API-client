<?php

namespace HappyR\LinkedIn\Http;

/**
 * Class RequestTest
 *
 * @author Tobias Nyholm
 *
 */
class RequestTest extends \PHPUnit_Framework_TestCase
{
    public function testPrepareParams()
    {
        $dummy=new Dummy();
        $result=$dummy->prepareParams('url', array(), 'GET', null);

        //make sure we include all values from $curlOptions
        $this->assertCount(0, @array_diff(Request::$curlOptions, $result));

        //test url
        $this->assertEquals('url', $result[CURLOPT_URL]);

        //we should not have any extra http headers but "Expect:"
        $this->assertCount(1, $result[CURLOPT_HTTPHEADER]);
        $this->assertEquals('Expect:',$result[CURLOPT_HTTPHEADER][0]);
    }

    public function testPrepareParamsContentType()
    {
        $dummy=new Dummy();
        $result=$dummy->prepareParams('url', array(), 'GET', null);
        $this->assertFalse(in_array('Content-Type: application/json', $result[CURLOPT_HTTPHEADER]));
        $this->assertFalse(in_array('Content-Type: text/xml', $result[CURLOPT_HTTPHEADER]));

        $result=$dummy->prepareParams('url', array(), 'GET', 'json');
        $this->assertTrue(in_array('Content-Type: application/json', $result[CURLOPT_HTTPHEADER]));

        $result=$dummy->prepareParams('url', array(), 'GET', 'xml');
        $this->assertTrue(in_array('Content-Type: text/xml', $result[CURLOPT_HTTPHEADER]));
    }

    public function testPrepareParamsPost()
    {
        $dummy=new Dummy();
        $result=$dummy->prepareParams('url', array('foo'=>'bar', 'baz'=>'biz'), 'post', null);
        $this->assertEquals('foo=bar&baz=biz', $result[CURLOPT_POSTFIELDS]);

        $params=array('foo'=>'bar', 'baz'=>'biz');
        $result=$dummy->prepareParams('url', $params, 'post', 'json');
        $this->assertEquals(json_encode($params), $result[CURLOPT_POSTFIELDS]);

        //make sure we don't json encode a json encoded string
        $result=$dummy->prepareParams('url', json_encode($params), 'post', 'json');
        $this->assertEquals(json_encode($params), $result[CURLOPT_POSTFIELDS]);

        $params='<xml><param>1</param></xml>';
        $result=$dummy->prepareParams('url', $params, 'post', 'xml');
        $this->assertEquals($params, $result[CURLOPT_POSTFIELDS]);
    }
}

/**
 * Class Dummy to expose protected params
 */
class Dummy extends Request
{
    public function prepareParams($url, $params, $method, $contentType)
    {
        return parent::prepareParams($url, $params, $method, $contentType);
    }
}