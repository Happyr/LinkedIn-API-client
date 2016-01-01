<?php

namespace Happyr\LinkedIn\Exception;

/**
 * Class LoginErrorTest.
 *
 * @author Tobias Nyholm
 */
class LoginErrorTest extends \PHPUnit_Framework_TestCase
{
    public function testGetters()
    {
        $error = new LoginError('foo', 'bar');

        $this->assertEquals('foo', $error->getName());
        $this->assertEquals('bar', $error->getDescription());
    }
}
