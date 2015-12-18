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
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('set')->once()->with('code', 'newCode')
            ->shouldReceive('set')->once()->with('access_token', 'at')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getCode', 'getStorage', 'getAccessTokenFromCode'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getAccessTokenFromCode')->will($this->returnValue('at'));
        $linkedIn->expects($this->once())->method('getCode')->will($this->returnValue('newCode'));

        $this->assertEquals('at', $linkedIn->fetchNewAccessToken());
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testFetchNewAccessTokenFail()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('clearAll')->once()
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getCode', 'getStorage', 'getAccessTokenFromCode'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getAccessTokenFromCode');
        $linkedIn->expects($this->once())->method('getCode')->will($this->returnValue('newCode'));

        $linkedIn->fetchNewAccessToken();
    }

    public function testFetchNewAccessTokenNoCode()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->with('code')->andReturn('foobar')
            ->shouldReceive('get')->once()->with('access_token', null)->andReturn('baz')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getCode', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getCode');

        $this->assertEquals('baz', $linkedIn->fetchNewAccessToken());
    }

    public function testFetchNewAccessTokenSameCode()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->once()->with('access_token', null)->andReturn('baz')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getCode', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getCode')->will($this->returnValue(null));

        $this->assertEquals('baz', $linkedIn->fetchNewAccessToken());
    }

    public function testGetAccessTokenFromCodeEmpty()
    {
        $this->assertNull($this->ln->getAccessTokenFromCode(''));
        $this->assertNull($this->ln->getAccessTokenFromCode(null));
        $this->assertNull($this->ln->getAccessTokenFromCode(false));
    }

    public function testGetAccessTokenFromCode()
    {
        $code = 'code';
        $response = array('access_token' => 'foobar', 'expires_in' => 10);
        $linkedIn = $this->prepareGetAccessTokenFromCode($code, $response);

        $token = $linkedIn->getAccessTokenFromCode($code);
        $this->assertEquals('foobar', $token, 'Standard get access token form code');

        $response = array('foo' => 'bar');
        $linkedIn = $this->prepareGetAccessTokenFromCode($code, $response);

        $this->assertNull($linkedIn->getAccessTokenFromCode($code), 'Found array but no access token');

        $response = '';
        $linkedIn = $this->prepareGetAccessTokenFromCode($code, $response);

        $this->assertNull($linkedIn->getAccessTokenFromCode($code), 'Empty result');
        $this->assertNull($linkedIn->getAccessTokenFromCode(null), 'Empty result');
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
        $generator = m::mock('Happyr\LinkedIn\Http\UrlGenerator')
            ->shouldReceive('getCurrentUrl')->once()->andReturn($currentUrl)
            ->shouldReceive('getUrl')->once()->with(
                'www',
                'uas/oauth2/accessToken'
            )->andReturn('url')
            ->getMock();
        $httpClient = m::mock('Happyr\HttpAutoDiscovery\Client')
            ->shouldReceive('send')->once()->with('POST', 'url', [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ], http_build_query(array(
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $currentUrl,
                'client_id' => self::APP_ID,
                'client_secret' => self::APP_SECRET,
            )))->andReturn($response)
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getHttpClient', 'getUrlGenerator'), array(self::APP_ID, self::APP_SECRET));
        $linkedIn->expects($this->any())->method('getUrlGenerator')->will($this->returnValue($generator));
        $linkedIn->expects($this->once())->method('getHttpClient')->will($this->returnValue($httpClient));

        return $linkedIn;
    }

    public function testEstablishCSRFTokenState()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->with('state', null)->andReturn(null, 'state')
            ->shouldReceive('set')->once()->with('state', \Mockery::on(function (&$param) {
                    return !empty($param);
                }))
            ->getMock();

        $this->ln->setStorage($storage);

        $this->ln->establishCSRFTokenState();
        $this->ln->establishCSRFTokenState();
    }

    /**
     * Test with no previous user.
     */
    public function testGetUserFromAvailableData()
    {
        $expected = 'foobar';
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('set')->once()->with('user', $expected)
            ->shouldReceive('get')->once()->with('user', null)->andReturn(null)
            ->shouldReceive('get')->once()->with('access_token')->andReturn('access_token')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getAccessToken', 'getUserFromAccessToken', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getAccessToken')->will($this->returnValue('access_token'));
        $linkedIn->expects($this->once())->method('getUserFromAccessToken')->will($this->returnValue($expected));

        $this->assertEquals($expected, $linkedIn->getUserFromAvailableData());
    }

    /**
     * Test with previous user.
     */
    public function testGetUserFromAvailableDataExistingUser()
    {
        $expected = 'user';
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->once()->with('user', null)->andReturn($expected)
            ->shouldReceive('get')->once()->with('access_token')->andReturn('access_token')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getAccessToken', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getAccessToken')->will($this->returnValue('access_token'));

        $this->assertEquals($expected, $linkedIn->getUserFromAvailableData());
    }

    /**
     * Test with previous user but new access token.
     */
    public function testGetUserFromAvailableDataExistingUserDifferentAccessToken()
    {
        $expected = 'foobar';
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('set')->once()->with('user', $expected)
            ->shouldReceive('get')->once()->with('user', null)->andReturn('user')
            ->shouldReceive('get')->once()->with('access_token')->andReturn('access_token')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getAccessToken', 'getUserFromAccessToken', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getAccessToken')->will($this->returnValue('new_access_token'));
        $linkedIn->expects($this->once())->method('getUserFromAccessToken')->will($this->returnValue($expected));

        $this->assertEquals($expected, $linkedIn->getUserFromAvailableData());
    }

    /**
     * Test when getUserFromAccessToken fails.
     */
    public function testGetUserFromAvailableDataFailedToGetUser()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('clearAll')->once()
            ->shouldReceive('get')->once()->with('user', null)->andReturn(null)
            ->shouldReceive('get')->once()->with('access_token')->andReturn('access_token')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getAccessToken', 'getUserFromAccessToken', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->any())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('getAccessToken')->will($this->returnValue('new_access_token'));
        $linkedIn->expects($this->once())->method('getUserFromAccessToken')->will($this->returnValue(null));

        $this->assertNull($linkedIn->getUserFromAvailableData());
    }

    public function testGetUserFromAccessToken()
    {
        $expected = 'foobar';
        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('api'), array(), '', false);
        $linkedIn->expects($this->once())->method('api')->will($this->returnValue($expected));

        $this->assertEquals($expected, $linkedIn->getUserFromAccessToken());
    }

    public function testGetUserFromAccessTokenFail()
    {
        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('api'), array(), '', false);
        $linkedIn->expects($this->once())->method('api')->will($this->throwException(new LinkedInApiException('foobar')));

        $this->assertNull($linkedIn->getUserFromAccessToken());
    }

    public function testGetCodeEmpty()
    {
        unset($_REQUEST['code']);
        $this->assertNull($this->ln->getCode());
    }

    public function testGetCode()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('clear')->once()->with('state')
            ->shouldReceive('get')->once()->with('code')->andReturn(null)
            ->getMock();
        $state = 'bazbar';

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getState', 'setState', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->once())->method('getStorage')->will($this->returnValue($storage));
        $linkedIn->expects($this->once())->method('setState')->with($this->equalTo(null));
        $linkedIn->expects($this->once())->method('getState')->will($this->returnValue($state));

        $_REQUEST['code'] = 'foobar';
        $_REQUEST['state'] = $state;

        $this->assertEquals('foobar', $linkedIn->getCode());
    }

    /**
     * @expectedException \Happyr\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testGetCodeInvalidCode()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->once()->with('code')->andReturn(null)
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getState', 'getStorage'), array(), '', false);
        $linkedIn->expects($this->once())->method('getState')->will($this->returnValue('bazbar'));
        $linkedIn->expects($this->once())->method('getStorage')->will($this->returnValue($storage));

        $_REQUEST['code'] = 'foobar';
        $_REQUEST['state'] = 'invalid';

        $this->assertEquals('foobar', $linkedIn->getCode());
    }

    public function testGetCodeUsedCode()
    {
        $storage = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface')
            ->shouldReceive('get')->once()->with('code')->andReturn('foobar')
            ->getMock();

        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedInDummy', array('getStorage'), array(), '', false);
        $linkedIn->expects($this->once())->method('getStorage')->will($this->returnValue($storage));

        $_REQUEST['code'] = 'foobar';

        $this->assertEquals(null, $linkedIn->getCode());
    }

    /**
     * Test a call to getAccessToken when there is no token.
     */
    public function testGetAccessTokenEmpty()
    {
        $token = 'token';
        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('fetchNewAccessToken', 'setAccessToken'), array(), '', false);
        $linkedIn->expects($this->once())->method('fetchNewAccessToken')->will($this->returnValue($token));
        $linkedIn->expects($this->once())->method('setAccessToken')->with($token);

        $linkedIn->getAccessToken();
    }

    public function testAccessTokenAccessors()
    {
        $token = 'token';
        $linkedIn = $this->getMock('Happyr\LinkedIn\LinkedIn', array('fetchNewAccessToken'), array(), '', false);
        $linkedIn->expects($this->never())->method('fetchNewAccessToken');

        $linkedIn->setAccessToken($token);
        $result = $linkedIn->getAccessToken();

        $this->assertEquals($token, $result);
    }

    public function testRequestAccessors()
    {
        // test default
        $this->assertInstanceOf('Http\Client\HttpClient', $this->ln->getHttpClient());

        $object = m::mock('Http\Adapter\Guzzle6HttpAdapter');
        $this->ln->setHttpClient($object);
        $this->assertEquals($object, $this->ln->getHttpClient());
    }

    public function testGeneratorAccessors()
    {
        // test default
        $this->assertInstanceOf('Happyr\LinkedIn\Http\UrlGenerator', $this->ln->getUrlGenerator());

        $object = m::mock('Happyr\LinkedIn\Http\UrlGenerator');
        $this->ln->setUrlGenerator($object);
        $this->assertEquals($object, $this->ln->getUrlGenerator());
    }

    public function testStorageAccessors()
    {
        // test default
        $this->assertInstanceOf('Happyr\LinkedIn\Storage\SessionStorage', $this->ln->getStorage());

        $object = m::mock('Happyr\LinkedIn\Storage\DataStorageInterface');
        $this->ln->setStorage($object);
        $this->assertEquals($object, $this->ln->getStorage());
    }

    public function testStateAccessors()
    {
        $state = 'foobar';
        $this->ln->setState($state);
        $this->assertEquals($state, $this->ln->getState());
    }

    public function testHasError()
    {
        unset($_GET['error']);
        $this->assertFalse($this->ln->hasError());

        $_GET['error'] = 'foobar';
        $this->assertTrue($this->ln->hasError());
    }

    public function testGetError()
    {
        unset($_GET['error']);
        unset($_GET['error_description']);

        $this->assertNull($this->ln->getError());

        $_GET['error'] = 'foo';
        $_GET['error_description'] = 'bar';

        $this->assertEquals('foo', $this->ln->getError()->getName());
        $this->assertEquals('bar', $this->ln->getError()->getDescription());
    }

    public function testGetErrorWithMissingDescription()
    {
        unset($_GET['error']);
        unset($_GET['error_description']);

        $_GET['error'] = 'foo';

        $this->assertEquals('foo', $this->ln->getError()->getName());
        $this->assertNull($this->ln->getError()->getDescription());
    }

    public function testFormatAccessors()
    {
        //test default
        $this->assertEquals('json', $this->ln->getFormat());

        $format = 'foo';
        $this->ln->setFormat($format);
        $this->assertEquals($format, $this->ln->getFormat());
    }
}

