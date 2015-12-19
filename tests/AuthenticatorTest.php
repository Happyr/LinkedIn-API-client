<?php

namespace Happyr\LinkedIn;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Happyr\LinkedIn\Exceptions\LinkedInApiException;
use Mockery as m;

/**
 *
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class AuthenticatorTest extends \PHPUnit_Framework_TestCase
{
    const APP_ID = '123456789';
    const APP_SECRET = '987654321';

    private function getRequestManagerMock()
    {
        return m::mock('Happyr\LinkedIn\Http\RequestManager');
    }

    public function testGetLoginUrl()
    {
        $expected = 'loginUrl';
        $state = 'random';
        $params = array(
            'response_type' => 'code',
            'client_id' => self::APP_ID,
            'redirect_uri' => 'currentUrl',
            'state' => $state,
        );

        $storage = $this->getMock('Happyr\LinkedIn\Storage\DataStorageInterface');

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('establishCSRFTokenState', 'getState', 'getStorage'), array($this->getRequestManagerMock(), self::APP_ID, self::APP_SECRET));
        $auth->expects($this->exactly(2))->method('establishCSRFTokenState')->willReturn(null);
        $auth->expects($this->any())->method('getState')->will($this->returnValue($state));
        $auth->expects($this->exactly(2))->method('getStorage')->will($this->returnValue($storage));

        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator')
            ->shouldReceive('getCurrentUrl')->once()->andReturn('currentUrl')
            ->shouldReceive('getUrl')->once()->with('www', 'uas/oauth2/authorization', $params)->andReturn($expected)
            ->getMock();

        $this->assertEquals($expected, $auth->getLoginUrl($generator));

        /*
         * Test with a url in the param
         */
        $otherUrl = 'otherUrl';
        $scope = array('foo', 'bar', 'baz');
        $params = array(
            'response_type' => 'code',
            'client_id' => self::APP_ID,
            'redirect_uri' => $otherUrl,
            'state' => $state,
            'scope' => 'foo bar baz',
        );

        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator')
            ->shouldReceive('getUrl')->once()->with('www', 'uas/oauth2/authorization', $params)->andReturn($expected)
            ->getMock();

        $this->assertEquals($expected, $auth->getLoginUrl($generator, array('redirect_uri' => $otherUrl, 'scope' => $scope)));
    }

    public function testFetchNewAccessToken()
    {
        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator');
        $code = 'newCode';
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('set')->once()->with('code', $code)
            ->shouldReceive('set')->once()->with('access_token', 'at')
            ->getMock();

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getCode', 'getStorage', 'getAccessTokenFromCode'), array(), '', false);
        $auth->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $auth->expects($this->once())->method('getAccessTokenFromCode')->with($generator, $code)->will($this->returnValue('at'));
        $auth->expects($this->once())->method('getCode')->will($this->returnValue($code));

        $this->assertEquals('at', $auth->fetchNewAccessToken($generator));
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testFetchNewAccessTokenFail()
    {

        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator');
        $code = 'newCode';
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('clearAll')->once()
            ->getMock();

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getCode', 'getStorage', 'getAccessTokenFromCode'), array(), '', false);
        $auth->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $auth->expects($this->once())->method('getAccessTokenFromCode')->with($generator, $code);
        $auth->expects($this->once())->method('getCode')->will($this->returnValue($code));

        $auth->fetchNewAccessToken($generator);
    }

    public function testFetchNewAccessTokenNoCode()
    {
        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator');
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->with('code')->andReturn('foobar')
            ->shouldReceive('get')->once()->with('access_token', null)->andReturn('baz')
            ->getMock();


        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getCode', 'getStorage'), array(), '', false);
        $auth->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $auth->expects($this->once())->method('getCode');

        $this->assertEquals('baz', $auth->fetchNewAccessToken($generator));
    }

    public function testGetAccessTokenFromCodeEmpty()
    {
        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator');

        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getAccessTokenFromCode');
        $method->setAccessible(true);
        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array(), array(), '', false);

        $this->assertNull($method->invoke($auth, $generator, ''));
        $this->assertNull($method->invoke($auth, $generator, null));
        $this->assertNull($method->invoke($auth, $generator, false));
    }

    public function testGetAccessTokenFromCode()
    {
        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getAccessTokenFromCode');
        $method->setAccessible(true);

        $code = 'code';
        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator')
            ->shouldReceive('getUrl')->with(
                'www',
                'uas/oauth2/accessToken'
            )->andReturn('url')
            ->getMock();

        $response = array('access_token' => 'foobar', 'expires_in' => 10);
        $auth = $this->prepareGetAccessTokenFromCode($code, $response);
        $token = $method->invoke($auth, $generator, $code);
        $this->assertEquals('foobar', $token, 'Standard get access token form code');

        $response = array('foo' => 'bar');
        $auth = $this->prepareGetAccessTokenFromCode($code, $response);
        $this->assertNull($method->invoke($auth, $generator, $code), 'Found array but no access token');

        $response = '';
        $auth = $this->prepareGetAccessTokenFromCode($code, $response);
        $this->assertNull($method->invoke($auth, $generator, $code), 'Empty result');
    }

    /**
     * Default stuff for GetAccessTokenFromCode.
     *
     * @param $response
     *
     * @return array
     */
    protected function prepareGetAccessTokenFromCode($code, $responseData)
    {
        $response = new Response(200, [], json_encode($responseData));
        $currentUrl = 'foobar';

        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->with('redirect_url')->andReturn($currentUrl)
            ->getMock();


        $requestManager = m::mock('Happyr\LinkedIn\Http\RequestManager')
            ->shouldReceive('sendRequest')->once()->with('POST', 'url', [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ], http_build_query(array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $currentUrl,
                'client_id' => self::APP_ID,
                'client_secret' => self::APP_SECRET,
            )))->andReturn($response)
            ->getMock();

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getStorage'), array($requestManager, self::APP_ID, self::APP_SECRET));
        $auth->expects($this->any())->method('getStorage')->will($this->returnValue($storage));

        return $auth;
    }

    public function testEstablishCSRFTokenState()
    {
        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'establishCSRFTokenState');
        $method->setAccessible(true);

        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->with('state', null)->andReturn(null, 'state')
            ->shouldReceive('set')->once()->with('state', \Mockery::on(function (&$param) {
                    return !empty($param);
                }))
            ->getMock();


        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getStorage'), array(), '', false);
        $auth->expects($this->any())->method('getStorage')->will($this->returnValue($storage));


        // Make sure we only set the state once
        $method->invoke($auth);
        $method->invoke($auth);
    }

    public function testGetCodeEmpty()
    {
        unset($_REQUEST['code']);
        unset($_GET['code']);

        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getCode');
        $method->setAccessible(true);
        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array(), array(), '', false);

        $this->assertNull($method->invoke($auth));
    }

    public function testGetCode()
    {
        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getCode');
        $method->setAccessible(true);

        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('clear')->once()->with('state')
            ->shouldReceive('get')->once()->with('code')->andReturn(null)
            ->getMock();
        $state = 'bazbar';

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getState', 'setState', 'getStorage'), array(), '', false);
        $auth->expects($this->once())->method('getStorage')->will($this->returnValue($storage));
        $auth->expects($this->once())->method('setState')->with($this->equalTo(null));
        $auth->expects($this->once())->method('getState')->will($this->returnValue($state));

        $_REQUEST['code'] = 'foobar';
        $_REQUEST['state'] = $state;

        $this->assertEquals('foobar', $method->invoke($auth));
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testGetCodeInvalidCode()
    {
        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getCode');
        $method->setAccessible(true);

        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->once()->with('code')->andReturn(null)
            ->getMock();

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getState', 'getStorage'), array(), '', false);
        $auth->expects($this->once())->method('getState')->will($this->returnValue('bazbar'));
        $auth->expects($this->once())->method('getStorage')->will($this->returnValue($storage));

        $_REQUEST['code'] = 'foobar';
        $_REQUEST['state'] = 'invalid';

        $this->assertEquals('foobar', $method->invoke($auth));
    }

    public function testGetCodeUsedCode()
    {
        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getCode');
        $method->setAccessible(true);

        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->once()->with('code')->andReturn('foobar')
            ->getMock();

        $auth = $this->getMock('Happyr\LinkedIn\Authenticator', array('getStorage'), array(), '', false);
        $auth->expects($this->once())->method('getStorage')->will($this->returnValue($storage));

        $_REQUEST['code'] = 'foobar';

        $this->assertEquals(null, $method->invoke($auth));
    }

    public function testStorageAccessors()
    {
        $method = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getStorage');
        $method->setAccessible(true);
        $requestManager = m::mock('Happyr\LinkedIn\Http\RequestManager');
        $auth = new Authenticator($requestManager, self::APP_ID, self::APP_SECRET);

        // test default
        $this->assertInstanceOf('Happyr\LinkedIn\Storage\SessionStorage', $method->invoke($auth));

        $object = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface');
        $auth->setStorage($object);
        $this->assertEquals($object, $method->invoke($auth));
    }

    public function testStateAccessors()
    {
        $set = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'setState');
        $set->setAccessible(true);
        $get = new \ReflectionMethod('Happyr\LinkedIn\Authenticator', 'getState');
        $get->setAccessible(true);

        $requestManager = m::mock('Happyr\LinkedIn\Http\RequestManager');
        $auth = new Authenticator($requestManager, self::APP_ID, self::APP_SECRET);

        $state = 'foobar';
        $set->invoke($auth, $state);
        $this->assertEquals($state, $get->invoke($auth));
    }

}

