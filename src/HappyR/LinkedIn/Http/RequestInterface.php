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
    public function create($url, $params=array());
} 