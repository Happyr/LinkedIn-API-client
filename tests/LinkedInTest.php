<?php

namespace Happyr\LinkedIn;

use GuzzleHttp\Psr7\Response;
use Happyr\LinkedIn\Http\RequestManager;
use Happyr\LinkedIn\Http\UrlGenerator;
use Mockery as m;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LinkedInTest extends \PHPUnit_Framework_TestCase
{
    const APP_ID = '123456789';
    const APP_SECRET = '987654321';

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testApi()
    {
        $resource = 'resource';
        $token = 'token';
        $urlParams = ['url' => 'foo'];
        $postParams = ['post' => 'bar'];
        $method = 'GET';
        $expected = ['foobar' => 'test'];
        $response = new Response(200, [], json_encode($expected));
        $url = 'http://example.com/test';

        $headers = ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json', 'x-li-format' => 'json'];

        $mockGenerator = m::mock(UrlGenerator::class)->makePartial();
        $mockGenerator->shouldReceive('getUrl')->once()->withArgs(['api', $resource, ['url' => 'foo', 'format' => 'json']])->andReturn($url);

        $requestManager = m::mock(RequestManager::class)->makePartial();
        $requestManager->shouldReceive('sendRequest')->once()->withArgs([$method, $url, $headers, json_encode($postParams)])->andReturn($response);


        $linkedIn = m::mock(LinkedIn::class, [self::APP_ID, self::APP_SECRET])->makePartial();
        $linkedIn->shouldReceive('getAccessToken')->once()->andREturn($token);

        $linkedIn->setUrlGenerator($mockGenerator);
        $linkedIn->setRequestManager($requestManager);

        $result = $linkedIn->api($method, $resource, ['query' => $urlParams, 'json' => $postParams]);
        $this->assertEquals($expected, $result);
    }

    public function testIsAuthenticated()
    {

        $linkedIn = m::mock(LinkedIn::class, [self::APP_ID, self::APP_SECRET])->makePartial();
        $linkedIn->shouldReceive('getAccessToken')->once()->andReturnNull();
        $this->assertFalse($linkedIn->isAuthenticated());

        $linkedIn = m::mock(LinkedIn::class, [self::APP_ID, self::APP_SECRET])->makePartial();
        $linkedIn->shouldReceive('api')->once()->andReturn(['id' => 4711]);
        $linkedIn->shouldReceive('getAccessToken')->once()->andReturn('token');
        $this->assertTrue($linkedIn->isAuthenticated());

        $linkedIn = m::mock(LinkedIn::class, [self::APP_ID, self::APP_SECRET])->makePartial();
        $linkedIn->shouldReceive('api')->once()->andReturn(['foobar' => 4711]);
        $linkedIn->shouldReceive('getAccessToken')->once()->andReturn('token');
        $this->assertFalse($linkedIn->isAuthenticated());
    }

    /**
     * Test a call to getAccessToken when there is no token.
     */
    public function testAccessTokenAccessors()
    {
        $token = 'token';


        $auth = m::mock(Authenticator::class)->makePartial();
        $auth->shouldReceive('fetchNewAccessToken')->once()->andREturn($token);


        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);
        $linkedIn->setAuthenticator($auth);


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

        $mock = m::mock(UrlGenerator::class);
        $linkedIn->setUrlGenerator($mock);
        $this->assertEquals($mock, $get->invoke($linkedIn));
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

        $mockUrlGenerator = m::mock(UrlGenerator::class);
        $mockUrlGenerator->shouldReceive('getCurrentUrl')->once()->andReturn($currentUrl);
        $mockUrlGenerator->shouldReceive('getUrl')->once()->andReturn('something');

        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);

        $linkedIn->setUrlGenerator($mockUrlGenerator);

        //$linkedIn->shouldReceive('getUrlGenerator')->once()->andReturn($urlgenerator);

        $this->assertNotEmpty($linkedIn->getLoginUrl());
    }

    public function testLoginUrlWithParameter()
    {
        $loginUrl = 'result';
        $otherUrl = 'otherUrl';

        $generator = m::mock(UrlGenerator::class)->makePartial();
        $generator->shouldReceive('getLoginUrl')->withAnyArgs()->andReturn($loginUrl);


        $mockAuth = m::mock(Authenticator::class)->makePartial();
        $mockAuth->shouldReceive('getLoginUrl')->once()->withArgs([$generator, ['redirect_uri' => $otherUrl]])->andReturn($otherUrl);


        $linkedIn = new LinkedIn(self::APP_ID, self::APP_SECRET);
        $linkedIn->setUrlGenerator($generator);
        $linkedIn->setAuthenticator($mockAuth);


        $this->assertContains($otherUrl, $linkedIn->getLoginUrl(['redirect_uri' => $otherUrl]));
    }
}
