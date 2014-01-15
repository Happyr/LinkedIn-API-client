<?php

/**
 * This demonstrates how to authenticate with LinkedIn and send api requests
 */

/*
 * First you need to make sure you've used composers auto load. You have is probably 
 * already done this before. You usually don't bother..
 */
//require_once "../vendor/autoload.php"

$linkedIn=new HappyR\LinkedIn\LinkedIn('app_id', 'app_secret');

//if not authenticated
if (!$linkedIn->isAuthenticated()) {
    $url = $linkedIn->getLoginUrl();
    echo "<a href='$url'>Login with LinkedIn</a>";
    exit();
}

//we know that the user is authenticated now
$user=$linkedIn->api('v1/people/~:(firstName,lastName)');

echo "Welcome ".$user['firstName'];