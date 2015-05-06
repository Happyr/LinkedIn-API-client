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
}
