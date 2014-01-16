<?php


namespace HappyR\LinkedIn\Http;


/**
 * Class RequestInterface
 *
 * @author Tobias Nyholm
 *
 */
interface RequestInterface
{
    public function send($url, $params=array(), $method='GET');
}