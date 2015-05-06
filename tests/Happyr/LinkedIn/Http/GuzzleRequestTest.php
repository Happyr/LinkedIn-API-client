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
            ->setMethods(array('getClient'))
            ->getMock();

        $guzzleRequest->expects($this->once())->method('getClient')->willReturn($client);
        $client->expects($this->once())->method('send')->with($this->equalTo($request))->willReturn($response);

        $result = $guzzleRequest->send('method', 'url', $options);
        $this->assertEquals('body', $result);
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testSendFail()
    {
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
            ->setMethods(array('getClient'))
            ->getMock();

        $guzzleRequest->expects($this->once())->method('getClient')->willReturn($client);

        $client->expects($this->once())->method('send')->with($this->equalTo($request))->will($this->throwException(new TransferException()));

        $guzzleRequest->send('method', 'url', $options);
    }
}