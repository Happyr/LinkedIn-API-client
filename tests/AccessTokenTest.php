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

    public function testConstructor()
    {
        $token = new AccessToken('foobar', 10);
        $this->assertInstanceOf('\DateTime', $token->getExpiresAt());
        $this->assertEquals('foobar', $token->getToken());

        $token = new AccessToken();
        $this->assertNull($token->getExpiresAt());
        $this->assertEmpty($token->getToken());

        $token = new AccessToken(null, new \DateTime('+2minutes'));
        $this->assertInstanceOf('\DateTime', $token->getExpiresAt());
    }

    public function testSetExpiresAt()
    {
        $token = new AccessToken();
        $token->setExpiresAt(new \DateTime('+2minutes'));
        $this->assertInstanceOf('\DateTime', $token->getExpiresAt());
    }

    public function testCreateWithString()
    {
        $token = AccessToken::create('foobar');
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('foobar', $token->__toString());
    }

    public function testCreateWithNoData()
    {
        $token = AccessToken::create('');
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('', $token->__toString());
    }

    public function testCreateWithAccessToken()
    {
        $orgToken = new AccessToken('foobar', 10);
        $token = AccessToken::create($orgToken);
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('foobar', $token->__toString());
    }

    public function testCreateWithSerializedAccessToken()
    {
        $orgToken = new AccessToken('foobar', 10);
        $orgTokenSerialized = serialize($orgToken);
        $token = AccessToken::create($orgTokenSerialized);
        $this->assertInstanceOf(AccessToken::class, $token);
        $this->assertEquals('foobar', $token->__toString());
    }
}
