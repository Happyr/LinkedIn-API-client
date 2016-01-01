<?php

namespace Happyr\LinkedIn;

use GuzzleHttp\Psr7\Response;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LinkedInTest extends \PHPUnit_Framework_TestCase
{
    const APP_ID = '123456789';
    const APP_SECRET = '987654321';

    public function testApi()
    {
        $resource = 'resource';
        $token = 'token';
        $urlParams = array('url' => 'foo');
        $postParams = array('post' => 'bar');
        $method = 'GET';
        $expected = array('foobar' => 'test');
        $response = new Response(200, [], json_encode($expected));
        $url = 'http://example.com/test';

        $headers = array('Authorization' => 'Bearer '.$token, 'Content-Type' => 'application/json', 'x-li-format' => 'json');

        $generator = $this->getMock('Happyr\LinkedIn\Http\UrlGenerator', array('getUrl'));
        $generator->expects($this->once())->method('getUrl')->with(
            $this->equalTo('api'),
            $this->equalTo($resource),
            $this->equalTo(array(
                'url' => 'foo',
                'format' => 'json',
            )))
            ->willReturn($url);

        $requestManager = $this->getMock('Happyr\LinkedIn\Http\RequestManager', array('sendRequest'));
        $requestManager->expects($this->once())->method('sendRequest')->with(
                $this->equalTo($method),
                $this->equalTo($url),
                $this->equalTo($headers),
                $this->equalTo(json_encode($postParams)))
            ->willReturn($response);

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('getAccessToken', 'getUrlGenerator', 'getRequestManager'), array(self::APP_ID, self::APP_SECRET));

        $linkedIn->expects($this->once())->method('getAccessToken')->willReturn($token);
        $linkedIn->expects($this->once())->method('getUrlGenerator')->willReturn($generator);
        $linkedIn->expects($this->once())->method('getRequestManager')->willReturn($requestManager);

        $result = $linkedIn->api($method, $resource, array('query' => $urlParams, 'json' => $postParams));
        $this->assertEquals($expected, $result);
    }

    public function testIsAuthenticated()
    {
        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('getAccessToken'), array(self::APP_ID, self::APP_SECRET));
        $linkedIn->expects($this->once())->method('getAccessToken')->willReturn(null);
        $this->assertFalse($linkedIn->isAuthenticated());

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('api', 'getAccessToken'), array(self::APP_ID, self::APP_SECRET));
        $linkedIn->expects($this->once())->method('getAccessToken')->willReturn('token');
        $linkedIn->expects($this->once())->method('api')->willReturn(array('id' => 4711));
        $this->assertTrue($linkedIn->isAuthenticated());

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('api', 'getAccessToken'), array(self::APP_ID, self::APP_SECRET));
        $linkedIn->expects($this->once())->method('getAccessToken')->willReturn('token');
        $linkedIn->expects($this->once())->method('api')->willReturn(array('foobar' => 4711));
        $this->assertFalse($linkedIn->isAuthenticated());
    }

    /**
     * Test a call to getAccessToken when there is no token.
     */
    public function testAccessTokenAccessors()
    {
        $token = 'token';

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('fetchNewAccessToken'), array(), '', false);
        $auth->expects($this->once())->method('fetchNewAccessToken')->will($this->returnValue($token));

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('getAuthenticator'), array(), '', false);
        $linkedIn->expects($this->once())->method('getAuthenticator')->willReturn($auth);

        // Make sure we go to the authenticator only once
        $this->assertEquals($token, $linkedIn->getAccessToken());
        $this->assertEquals($token, $linkedIn->getAccessToken());
    }

    public function testGeneratorAccessors()
    {
        $get = new \ReflectionMethod('Happyr\LinkedIn\LinkedIn', 'getUrlGenerator');
        $get->setAccessible(true);
        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);

        // test default
        $this->assertInstanceOf('Happyr\LinkedIn\Http\UrlGenerator', $get->invoke($linkedIn));

        $object = $this->getMock('Happyr\LinkedIn\Http\UrlGenerator');
        $linkedIn->setUrlGenerator($object);
        $this->assertEquals($object, $get->invoke($linkedIn));
    }

    public function testHasError()
    {
        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);

        unset($_GET['error']);
        $this->assertFalse($linkedIn->hasError());

        $_GET['error'] = 'foobar';
        $this->assertTrue($linkedIn->hasError());
    }

    public function testGetError()
    {
        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);

        unset($_GET['error']);
        unset($_GET['error_description']);

        $this->assertNull($linkedIn->getError());

        $_GET['error'] = 'foo';
        $_GET['error_description'] = 'bar';

        $this->assertEquals('foo', $linkedIn->getError()->getName());
        $this->assertEquals('bar', $linkedIn->getError()->getDescription());
    }

    public function testGetErrorWithMissingDescription()
    {
        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);

        unset($_GET['error']);
        unset($_GET['error_description']);

        $_GET['error'] = 'foo';

        $this->assertEquals('foo', $linkedIn->getError()->getName());
        $this->assertNull($linkedIn->getError()->getDescription());
    }

    public function testFormatAccessors()
    {
        $get = new \ReflectionMethod('Happyr\LinkedIn\LinkedIn', 'getFormat');
        $get->setAccessible(true);
        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);

        //test default
        $this->assertEquals('json', $get->invoke($linkedIn));

        $format = 'foo';
        $linkedIn->setFormat($format);
        $this->assertEquals($format, $get->invoke($linkedIn));
    }

    public function testLoginUrl()
    {
        $currentUrl = 'currentUrl';
        $loginUrl = 'result';

        $generator = $this->getMock('Happyr\LinkedIn\Http\UrlGenerator', array('getCurrentUrl'));
        $generator->expects($this->once())->method('getCurrentUrl')->willReturn($currentUrl);

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getLoginUrl'), array(), '', false);
        $auth->expects($this->once())->method('getLoginUrl')
            ->with($generator, array('redirect_uri' => $currentUrl))
            ->will($this->returnValue($loginUrl));

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('getAuthenticator', 'getUrlGenerator'), array(), '', false);
        $linkedIn->expects($this->once())->method('getAuthenticator')->willReturn($auth);
        $linkedIn->expects($this->once())->method('getUrlGenerator')->willReturn($generator);

        $linkedIn->getLoginUrl();
    }

    public function testLoginUrlWithParameter()
    {
        $loginUrl = 'result';
        $otherUrl = 'otherUrl';

        $generator = $this->getMock('Happyr\LinkedIn\Http\UrlGenerator');

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getLoginUrl'), array(), '', false);
        $auth->expects($this->once())->method('getLoginUrl')
            ->with($generator, array('redirect_uri' => $otherUrl))
            ->will($this->returnValue($loginUrl));

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('getAuthenticator', 'getUrlGenerator'), array(), '', false);
        $linkedIn->expects($this->once())->method('getAuthenticator')->willReturn($auth);
        $linkedIn->expects($this->once())->method('getUrlGenerator')->willReturn($generator);

        $linkedIn->getLoginUrl(array('redirect_uri' => $otherUrl));
    }
}
