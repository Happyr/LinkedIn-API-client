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
    const APP_ID = '123456789';
    const SECRET = '987654321';

    public function testConstructor() {
        $facebook = new LinkedInDummy(self::APP_ID,self::SECRET);

        $this->assertEquals($facebook->getAppId(), self::APP_ID,
            'Expect the App ID to be set.');
        $this->assertEquals($facebook->getAppSecret(), self::SECRET,
            'Expect the API secret to be set.');
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