<?php

namespace HappyR\LinkedIn;

use Mockery as m;

/**
 * Class LinkedInText
 *
 * @author Tobias Nyholm
 *
 */
class LinkedInTest extends \PHPUnit_Framework_TestCase
{
    const APP_ID='123456789';
    const APP_SECRET='987654321';
    /**
     * @var LinkedInDummy ln
     *
     */
    protected $ln;

    public function setUp()
    {
        $this->ln = new LinkedInDummy(self::APP_ID, self::APP_SECRET);
    }

    public function testConstructor()
    {
        $this->assertEquals($this->ln->getAppId(), '123456789',
            'Expect the App ID to be set.');
        $this->assertEquals($this->ln->getAppSecret(), '987654321',
            'Expect the API secret to be set.');
    }

    public function testApi()
    {
        $resource='resource';
        $token='token';
        $urlParams=array('url'=>'foo');
        $postParams=array('post'=>'bar');
        $method='GET';
        $expected=array('foobar'=>'test');
        $url='http://example.com/test';

        $generator = m::mock('HappyR\LinkedIn\Http\UrlGenerator')
            ->shouldReceive('getUrl')->once()->with(
                'api',
                $resource,
                array(
                    'url'=>'foo',
                    'oauth2_access_token'=>$token,
                    'format'=>'json',
                ))->andReturn($url)
            ->getMock();

        $request = m::mock('HappyR\LinkedIn\Http\RequestInterface')
            ->shouldReceive('send')->once()->with($url, $postParams, $method)->andReturn(json_encode($expected))
            ->getMock();

        $linkedIn=m::mock('HappyR\LinkedIn\LinkedIn[getAccessToken,getUrlGenerator,getRequest]', array('id', 'secret'))
            ->shouldReceive('getAccessToken')->once()->andReturn($token)
            ->shouldReceive('getUrlGenerator')->once()->andReturn($generator)
            ->shouldReceive('getRequest')->once()->andReturn($request)
            ->getMock();


        $result=$linkedIn->api($resource, $urlParams, $method, $postParams);
        $this->assertEquals($expected, $result);

    }

    public function testIsAuthenticated()
    {
        $linkedIn=m::mock('HappyR\LinkedIn\LinkedIn[getUserId]', array(1, 2))
            ->shouldReceive('getUserId')->once()->andReturn(null)
            ->getMock();
        $this->assertFalse($linkedIn->isAuthenticated());

        $linkedIn=m::mock('HappyR\LinkedIn\LinkedIn[getUserId]', array(1, 2))
            ->shouldReceive('getUserId')->once()->andReturn(3)
            ->getMock();
        $this->assertTrue($linkedIn->isAuthenticated());
    }

    public function testGetLoginUrl()
    {
        $expected='loginUrl';
        $state='random';
        $params=array(
            'response_type'=>'code',
            'client_id' => self::APP_ID,
            'redirect_uri' => 'currentUrl',
            'state' => $state,
        );



        $linkedIn=$this->getMock('HappyR\LinkedIn\LinkedIn', array('establishCSRFTokenState', 'getState'), array(self::APP_ID, self::APP_SECRET));
        $linkedIn->expects($this->any())->method('establishCSRFTokenState');
        $linkedIn->expects($this->any())->method('getState')->will($this->returnValue($state));

        $generator = m::mock('HappyR\LinkedIn\Http\UrlGenerator')
            ->shouldReceive('getCurrentUrl')->once()->andReturn('currentUrl')
            ->shouldReceive('getUrl')->once()->with('www','uas/oauth2/authorization', $params)->andReturn($expected)
            ->getMock();

        $linkedIn->setUrlGenerator($generator);

        $this->assertEquals($expected, $linkedIn->getLoginUrl());

        /*
         * Test with a url in the param
         */
        $otherUrl='otherUrl';
        $scope=array('foo', 'bar', 'baz');
        $params=array(
            'response_type'=>'code',
            'client_id' => self::APP_ID,
            'redirect_uri' => $otherUrl,
            'state' => $state,
            'scope' => 'foo bar baz',
        );

        $generator = m::mock('HappyR\LinkedIn\Http\UrlGenerator')
            ->shouldReceive('getCurrentUrl')->once()->andReturn('currentUrl')
            ->shouldReceive('getUrl')->once()->with('www','uas/oauth2/authorization', $params)->andReturn($expected)
            ->getMock();

        $linkedIn->setUrlGenerator($generator);

        $this->assertEquals($expected, $linkedIn->getLoginUrl(array('redirect_uri'=>$otherUrl, 'scope'=>$scope)));
    }

    public function testGetUser()
    {
        $user='user';
        $linkedIn=$this->getMock('HappyR\LinkedIn\LinkedIn', array('getUserFromAvailableData', 'getState'), array(), '', false);
        $linkedIn->expects($this->once())->method('getUserFromAvailableData')->will($this->returnValue($user));

        $this->assertEquals($user, $linkedIn->getUser());

        //test again to make sure getUserFromAvailableData() is not called again
        $this->assertEquals($user, $linkedIn->getUser());
    }

    public function testGetUserId()
    {
        $linkedIn=m::mock('HappyR\LinkedIn\LinkedIn[getUser]', array(self::APP_ID, self::APP_SECRET))
            ->shouldReceive('getUser')->andReturn(array('id'=>'foobar'), array(), null)
            ->getMock();

        $this->assertEquals('foobar', $linkedIn->getUserId());
        $this->assertEquals(null, $linkedIn->getUserId());
        $this->assertEquals(null, $linkedIn->getUserId());
    }

    /**
     * Test a call to getAccessToken when there is no token
     */
    public function testGetAccessTokenEmpty()
    {
        $token='token';
        $linkedIn=$this->getMock('HappyR\LinkedIn\LinkedIn', array('fetchNewAccessToken', 'setAccessToken'), array(), '', false);
        $linkedIn->expects($this->once())->method('fetchNewAccessToken')->will($this->returnValue($token));
        $linkedIn->expects($this->once())->method('setAccessToken')->with($token);

        $linkedIn->getAccessToken();
    }

    public function testAccessTokenAccessors()
    {
        $token='token';
        $linkedIn=$this->getMock('HappyR\LinkedIn\LinkedIn', array('fetchNewAccessToken'), array(), '', false);
        $linkedIn->expects($this->never())->method('fetchNewAccessToken');

        $linkedIn->setAccessToken($token);
        $result=$linkedIn->getAccessToken();

        $this->assertEquals($token, $result);
    }

    public function testRequestAccessors()
    {
        $object = m::mock('HappyR\LinkedIn\Http\RequestInterface');
        $this->ln->setRequest($object);
        $this->assertEquals($object, $this->ln->getRequest());
    }

    public function testGeneratorAccessors()
    {
        $object = m::mock('HappyR\LinkedIn\Http\UrlGenerator');
        $this->ln->setUrlGenerator($object);
        $this->assertEquals($object, $this->ln->getUrlGenerator());
    }

    public function testStorageAccessors()
    {
        $object = m::mock('HappyR\LinkedIn\Storage\DataStorage');
        $this->ln->setStorage($object);
        $this->assertEquals($object, $this->ln->getStorage());
    }

    public function testStateAccessors()
    {
        $state='foobar';
        $this->ln->setState($state);
        $this->assertEquals($state, $this->ln->getState());
    }

}



/**
 * Class LinkedInDummy
 *
 * @author Tobias Nyholm
 *
 */
class LinkedInDummy extends LinkedIn
{
    public function init($storage=null, $request=null, $generator=null)
    {
        if (!$storage) {
            $storage = m::mock('HappyR\LinkedIn\Storage\DataStorage');
        }

        if (!$request) {
            $request = m::mock('HappyR\LinkedIn\Http\RequestInterface');
        }

        if (!$generator) {
            $generator = m::mock('HappyR\LinkedIn\Http\UrlGenerator');
        }

        $this->storage=$storage;
        $this->request=$request;
        $this->urlGenerator=$generator;
    }

    public function  getUserFromAvailableData()
    {
        return parent::getUserFromAvailableData();
    }

    public function getUserFromAccessToken()
    {
        return parent::getUserFromAccessToken();
    }

    public function getCode()
    {
        return parent::getCode();
    }

    public function fetchNewAccessToken()
    {
        return parent::fetchNewAccessToken();
    }

    public function getAccessTokenFromCode($code, $redirectUri = null)
    {
        return parent::getAccessTokenFromCode($code, $redirectUri);
    }

    public function establishCSRFTokenState()
    {
        parent::establishCSRFTokenState();
    }

    public function getState()
    {
        return parent::getState();
    }

    public function setState($state)
    {
        return parent::setState($state);
    }
}