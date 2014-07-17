<?php


namespace HappyR\LinkedIn\Exceptions;


/**
 * Class LinkedInApiExceptionTest
 *
 * @author Tobias Nyholm
 *
 */
class LinkedInApiExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testExceptionConstructorWithErrorCode() {
        $code = 404;
        $e = new LinkedInApiException(array('error_code' => $code));
        $this->assertEquals($code, $e->getCode());
    }

    public function testExceptionConstructorWithInvalidErrorCode() {
        $e = new LinkedInApiException(array('error_code' => 'not an int'));
        $this->assertEquals(0, $e->getCode());
    }

    // this happens often despite the fact that it is useless
    public function testExceptionTypeFalse() {
        $e = new LinkedInApiException(false);
        $this->assertEquals('Exception', $e->getType());
    }

    public function testExceptionTypeMixedDraft00() {
        $e = new LinkedInApiException(array('error' => array('message' => 'foo')));
        $this->assertEquals('Exception', $e->getType());
    }

    public function testExceptionTypeDraft00() {
        $error = 'foo';
        $e = new LinkedInApiException(
            array('error' => array('type' => $error, 'message' => 'hello world')));
        $this->assertEquals($error, $e->getType());
    }

    public function testExceptionTypeDraft10() {
        $error = 'foo';
        $e = new LinkedInApiException(array('error' => $error));
        $this->assertEquals($error, $e->getType());
    }

    public function testExceptionTypeRest() {
        $error = 'foo';
        $e = new LinkedInApiException(array('error_msg' => $error));
        $this->assertEquals($error, $e->getMessage());
        $this->assertEquals('Exception', $e->getType());
    }

    public function testExceptionTypeDefault() {
        $e = new LinkedInApiException(array('error' => false));
        $this->assertEquals('Exception', $e->getType());
    }

    public function testExceptionToString() {
        $e = new LinkedInApiException(array(
            'error_code' => 1,
            'error_description' => 'foo',
        ));
        $this->assertEquals('Exception: 1: foo', (string) $e);
    }

    public function testGetResult()
    {
        $var='foobar';
        $e = new LinkedInApiException($var);

        $this->assertEquals($var, $e->getResult());
    }

} 