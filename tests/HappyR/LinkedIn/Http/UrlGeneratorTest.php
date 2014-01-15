<?php


namespace HappyR\LinkedIn\Http;

use HappyR\LinkedIn\Storage\DataStorage;

/**
 * Class UrlGeneratorTest
 *
 * @author Tobias Nyholm
 *
 */
class UrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    public function testDropLinkedInParams()
    {
        $gen = new DummyUrlGenerator();

        $test='foo=bar&code=foobar&baz=foo';
        $expected='?foo=bar&baz=foo';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        $test='code=foobar&baz=foo';
        $expected='?baz=foo';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        $test='foo=bar&code=foobar';
        $expected='?foo=bar';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        $test='code=foobar';
        $expected='';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        $test='';
        $expected='';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        /* ----------------- */

        $test='foo=bar&code=';
        $expected='?foo=bar';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        $test='code=';
        $expected='';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        $test='foo=bar&code';
        $expected='?foo=bar';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));

        $test='code';
        $expected='';
        $this->assertEquals($expected, $gen->dropLinkedInParams($test));
    }

    public function testHttpHost()
    {
        $real = 'foo.com';
        $_SERVER['HTTP_HOST'] = $real;
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'evil.com';
        $gen = new DummyUrlGenerator();
        $this->assertEquals($real, $gen->GetHttpHost());
    }

    public function testHttpProtocol()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $gen = new DummyUrlGenerator();
        $this->assertEquals('https', $gen->GetHttpProtocol());
    }

    public function testHttpHostForwarded()
    {
        $real = 'foo.com';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_X_FORWARDED_HOST'] = $real;
        $gen = new DummyUrlGenerator();
        $gen->setTrustForwarded(true);
        $this->assertEquals($real, $gen->GetHttpHost());
    }

    public function testHttpProtocolForwarded()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $gen = new DummyUrlGenerator();
        $gen->setTrustForwarded(true);
        $this->assertEquals('http', $gen->GetHttpProtocol());
    }

    public function testHttpProtocolForwardedSecure()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $gen = new DummyUrlGenerator();
        $this->assertEquals('https', $gen->GetHttpProtocol());
    }

    


}

class DummyUrlGenerator extends UrlGenerator
{
    public function getHttpHost()
    {
        return parent::getHttpHost();
    }

    public function getHttpProtocol()
    {
        return parent::getHttpProtocol();
    }

    public function dropLinkedInParams($query)
    {
        return parent::dropLinkedInParams($query);
    }
}