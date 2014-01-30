<?php

namespace HappyR\LinkedIn\Storage;

use Mockery as m;
use Illuminate\Support\Facades\Session;

/**
 * Class SessionStorageTest
 *
 * @author Andreas Creten
 *
 */
class IlluminateSessionStorageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \HappyR\LinkedIn\Storage\SessionStorage storage
     *
     */
    protected $storage;

    protected $prefix = 'linkedIn_';

    public function setUp()
    {
        $this->storage = new IlluminateSessionStorage();
    }

    public function testSet()
    {
        Session::shouldReceive('put')->once()->with($this->prefix.'code', 'foobar');

        $this->storage->set('code', 'foobar');
    }

    /**
     * @expectedException \HappyR\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testSetFail()
    {
        Session::shouldReceive('put')->once()->with($this->prefix.'code', 'baz')->andThrow('\HappyR\LinkedIn\Exceptions\LinkedInApiException');

        $this->storage->set('foobar', 'baz');
    }

    public function testGet()
    {

        $expected = 'foobar';
        Session::shouldReceive('get')->once()->with($this->prefix.'code')->andReturn($expected);
        $result = $this->storage->get('code', $expected);
        $this->assertEquals($expected, $result);

        $expected = 'foobar';
        Session::shouldReceive('get')->once()->with($this->prefix.'code')->andReturn(false);
        $result = $this->storage->get('nono', $expected);
        $this->assertEquals($expected, $result);
    }


    public function testClear()
    {
        Session::shouldReceive('forget')->once()->with($this->prefix.'code')->andReturn(true);
        $this->storage->clear('code');
    }

    /**
     * @expectedException \HappyR\LinkedIn\Exceptions\LinkedInApiException
     */
    public function testClearFail()
    {
        $this->storage->clear('foobar');
    }
}