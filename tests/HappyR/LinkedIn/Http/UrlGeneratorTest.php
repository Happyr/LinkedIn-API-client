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

    public function testGetUrl()
    {
        $gen=new DummyUrlGenerator();

        $expected='https://api.linkedin.com/?bar=baz';
        $this->assertEquals($expected, $gen->getUrl('api', '', array('bar'=>'baz')), 'No path');

        $expected='https://api.linkedin.com/foobar';
        $this->assertEquals($expected, $gen->getUrl('api', 'foobar'), 'Path does not begin with forward slash');
        $this->assertEquals($expected, $gen->getUrl('api', '/foobar'), 'Path begins with forward slash');

        $expected='https://api.linkedin.com/foobar?bar=baz';
        $this->assertEquals($expected, $gen->getUrl('api', 'foobar', array('bar'=>'baz')), 'One parameter');

        $expected='https://api.linkedin.com/foobar?bar=baz&a=b&c=d';
        $this->assertEquals($expected, $gen->getUrl('api', 'foobar', array('bar'=>'baz', 'a'=>'b', 'c'=>'d')), 'Many parameters');

        $expected='https://api.linkedin.com/foobar?bar=baz%20a%20b';
        $notExpected='https://api.linkedin.com/foobar?bar=baz+a+b';
        $this->assertEquals($expected, $gen->getUrl('api', 'foobar', array('bar'=>'baz a b')), 'Use of PHP_QUERY_RFC3986');
        $this->assertNotEquals($notExpected, $gen->getUrl('api', 'foobar', array('bar'=>'baz a b')), 'Dont use PHP_QUERY_RFC1738');

    }

    public function testGetCurrentURL() {
        $gen=$this->getMock('HappyR\LinkedIn\Http\UrlGenerator', array('getHttpProtocol', 'getHttpHost', 'dropLinkedInParams'), array());
        $gen->expects($this->any())->method('getHttpProtocol')->will($this->returnValue('http'));
        $gen->expects($this->any())->method('getHttpHost')->will($this->returnValue('www.test.com'));
        $gen->expects($this->any())->method('dropLinkedInParams')->will($this->returnCallback(function($arg){return empty($arg)?'':'?'.$arg;}));


        // fake the HPHP $_SERVER globals
        $_SERVER['REQUEST_URI'] = '/unit-tests.php?one=one&two=two&three=three';
        $this->assertEquals(
            'http://www.test.com/unit-tests.php?one=one&two=two&three=three',
            $gen->getCurrentUrl(),
            'getCurrentUrl function is changing the current URL');

        // ensure structure of valueless GET params is retained (sometimes
        // an = sign was present, and sometimes it was not)
        // first test when equal signs are present
        $_SERVER['REQUEST_URI'] = '/unit-tests.php?one=&two=&three=';
        $this->assertEquals(
            'http://www.test.com/unit-tests.php?one=&two=&three=',
            $gen->getCurrentUrl(),
            'getCurrentUrl function is changing the current URL');

        // now confirm that
        $_SERVER['REQUEST_URI'] = '/unit-tests.php?one&two&three';
        $this->assertEquals(
            'http://www.test.com/unit-tests.php?one&two&three',
            $gen->getCurrentUrl(),
            'getCurrentUrl function is changing the current URL'
        );

    }

    public function testGetCurrentURLPort80() {
        $gen=$this->getMock('HappyR\LinkedIn\Http\UrlGenerator', array('getHttpProtocol', 'getHttpHost', 'dropLinkedInParams'), array());
        $gen->expects($this->any())->method('getHttpProtocol')->will($this->returnValue('http'));
        $gen->expects($this->any())->method('getHttpHost')->will($this->returnValue('www.test.com:80'));
        $gen->expects($this->any())->method('dropLinkedInParams')->will($this->returnCallback(function($arg){return empty($arg)?'':'?'.$arg;}));

        //test port 80
        $_SERVER['REQUEST_URI'] = '/foobar.php';
        $this->assertEquals(
            'http://www.test.com/foobar.php',
            $gen->getCurrentUrl(),
            'port 80 should not be shown'
        );
    }

    public function testGetCurrentURLPort8080() {

        $gen=$this->getMock('HappyR\LinkedIn\Http\UrlGenerator', array('getHttpProtocol', 'getHttpHost', 'dropLinkedInParams'), array());
        $gen->expects($this->any())->method('getHttpProtocol')->will($this->returnValue('http'));
        $gen->expects($this->any())->method('getHttpHost')->will($this->returnValue('www.test.com:8080'));
        $gen->expects($this->any())->method('dropLinkedInParams')->will($this->returnCallback(function($arg){return empty($arg)?'':'?'.$arg;}));

        //test non default port 8080
        $_SERVER['REQUEST_URI'] = '/foobar.php';
        $this->assertEquals(
            'http://www.test.com:8080/foobar.php',
            $gen->getCurrentUrl(),
            'port 80 should not be shown'
        );


    }

    public function testHttpHost()
    {
        $real = 'foo.com';
        $_SERVER['HTTP_HOST'] = $real;
        $_SERVER['HTTP_X_FORWARDED_HOST'] = 'evil.com';
        $gen = new DummyUrlGenerator();
        $this->assertEquals($real, $gen->GetHttpHost());
    }

    public function testHttpProtocolApache()
    {
        $_SERVER['HTTPS'] = 'on';
        $gen = new DummyUrlGenerator();
        $this->assertEquals('https', $gen->GetHttpProtocol());
    }

    public function testHttpProtocolNginx()
    {
        $_SERVER['SERVER_PORT'] = '443';
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
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $gen = new DummyUrlGenerator();
        $this->assertEquals('http', $gen->GetHttpProtocol());


        $gen->setTrustForwarded(true);
        $this->assertEquals('https', $gen->GetHttpProtocol());
    }


    protected function tearDown()
    {
        unset($_SERVER['HTTPS']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        $_SERVER['HTTP_HOST'] = 'localhost';
        unset($_SERVER['HTTP_X_FORWARDED_HOST']);
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '';
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