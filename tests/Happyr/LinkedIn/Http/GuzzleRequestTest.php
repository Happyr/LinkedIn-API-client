<?php

namespace Happyr\LinkedIn\Http;

use GuzzleHttp\Exception\TransferException;

/**
 * Class RequestTest
 *
 * @author Tobias Nyholm
 *
 */
class GuzzleRequestTest extends \PHPUnit_Framework_TestCase
{
    public function testSend()
    {
        $params = array('params');
        $options = array('options');

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('GuzzleHttp\Message\Response')
            ->disableOriginalConstructor()
            ->setMethods(array('getBody'))
            ->getMock();
        $response->expects($this->once())->method('getBody')->willReturn('body');

        $client = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('send', 'createRequest'))
            ->getMock();

        $client->expects($this->once())->method('createRequest')->with(
            $this->equalTo('method'),
            $this->equalTo('url'),
            $this->equalTo($options))
            ->willReturn($request);

        $guzzleRequest = $this->getMockBuilder('Happyr\LinkedIn\Http\GuzzleRequest')
            ->disableOriginalConstructor()
            ->setMethods(array('getClient', 'createOptions'))
            ->getMock();

        $guzzleRequest->expects($this->once())->method('getClient')->willReturn($client);
        $guzzleRequest->expects($this->once())->method('createOptions')->with(
            $this->equalTo($params),
            $this->equalTo('method'),
            $this->equalTo('contentType'))
            ->willReturn($options);

        $client->expects($this->once())->method('send')->with($this->equalTo($request))->willReturn($response);


        $result = $guzzleRequest->send('url', $params, 'method', 'contentType');
        $this->assertEquals('body', $result);
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testSendFail()
    {
        $params = array('params');
        $options = array('options');

        $request = $this->getMockBuilder('GuzzleHttp\Message\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $client = $this->getMockBuilder('GuzzleHttp\Client')
            ->disableOriginalConstructor()
            ->setMethods(array('send', 'createRequest'))
            ->getMock();

        $client->expects($this->once())->method('createRequest')->with(
            $this->equalTo('method'),
            $this->equalTo('url'),
            $this->equalTo($options))
            ->willReturn($request);

        $guzzleRequest = $this->getMockBuilder('Happyr\LinkedIn\Http\GuzzleRequest')
            ->disableOriginalConstructor()
            ->setMethods(array('getClient', 'createOptions'))
            ->getMock();

        $guzzleRequest->expects($this->once())->method('getClient')->willReturn($client);
        $guzzleRequest->expects($this->once())->method('createOptions')->with(
            $this->equalTo($params),
            $this->equalTo('method'),
            $this->equalTo('contentType'))
            ->willReturn($options);

        $client->expects($this->once())->method('send')->with($this->equalTo($request))->will($this->throwException(new TransferException()));

        $guzzleRequest->send('url', $params, 'method', 'contentType');
    }

    public function testCallToModifyOptions()
    {
        $dummy = $this->getMockBuilder('Happyr\LinkedIn\Http\GuzzleDummy')
            ->disableOriginalConstructor()
            ->setMethods(array('modifyOptions'))
            ->getMock();

        $dummy->expects($this->once())->method('modifyOptions');

        $dummy->createOptions(array(), 'GET', null);
    }

    public function testCreateOptionsContentType()
    {
        $dummy=new GuzzleDummy();
        $result=$dummy->createOptions(array(), 'GET', null);
        $this->assertTrue(empty($result['headers']));

        $result=$dummy->createOptions(array(), 'GET', 'json');
        $this->assertTrue(isset($result['headers']['Content-Type']));
        $this->assertEquals($result['headers']['Content-Type'], 'application/json');

        $result=$dummy->createOptions(array(), 'GET', 'xml');
        $this->assertTrue(isset($result['headers']['Content-Type']));
        $this->assertEquals($result['headers']['Content-Type'], 'text/xml');
    }

    public function testCreateOptionsPost()
    {
        $dummy=new GuzzleDummy();
        $params = array('foo' => 'bar', 'baz' => 'biz');
        $result=$dummy->createOptions($params, 'post', null);
        $this->assertEquals($params, $result['body']);

        $params=array('foo'=>'bar', 'baz'=>'biz');
        $result=$dummy->createOptions($params, 'post', 'json');
        $this->assertEquals($params, $result['json']);

        $params='<xml><param>1</param></xml>';
        $result=$dummy->createOptions($params, 'post', 'xml');
        $this->assertEquals($params, $result['body']);
    }
}

/**
 * Class Dummy to expose protected params
 */
class GuzzleDummy extends GuzzleRequest
{
    public function createOptions($params, $method, $contentType)
    {
        return parent::createOptions($params, $method, $contentType);
    }
}