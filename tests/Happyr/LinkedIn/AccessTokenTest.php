<?php

namespace Happyr\LinkedIn;

/**
 * @author Tobias Nyholm
 */
class AccessTokenTest extends \PHPUnit_Framework_TestCase
{
    public function testToString()
    {
        $token = new AccessToken();
        $this->assertEquals('', $token);

        $token->setToken('foobar');
        $this->assertEquals('foobar', $token);
    }

    public function testConstructFromJson()
    {
        $token = new AccessToken();
        $token->constructFromJson(json_encode(array('access_token' => 'foobar', 'expires_in' => 10)));

        $this->assertInstanceOf('\DateTime', $token->getExpiresAt());
        $this->assertEquals('foobar', $token->getToken());

        $token = new AccessToken();
        $token->constructFromJson(json_encode(array('baz' => 'foobar')));

        $this->assertNull($token->getExpiresAt());
        $this->assertEmpty($token->getToken());
    }
}
