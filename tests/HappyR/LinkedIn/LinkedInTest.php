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
    /**
     * @var LinkedInDummy ln
     *
     */
    protected $ln;

    public function setUp()
    {
        $this->ln = new LinkedInDummy('123456789', '987654321');
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

    /**
     * Test a call to getAccessToken when there is not token
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
}