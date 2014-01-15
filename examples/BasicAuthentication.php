<?php

/**
 * This demonstrates how to authenticate
 */

//First you need to make sure you've used composers auto load
//require_once "../vendor/autoload.php"

$linkedIn=new \HappyR\LinkedIn\LinkedIn('app_id', 'app_secret');

//if not athenticated
if (!$linkedIn->isAuthenticated()) {
    $url = $linkedIn->getLoginUrl();
    echo "<a href='$url'>Login with LinkedIn</a>";
    exit();
}

//we know that the user is authenticated now
$user=$linkedIn->api('v1/people/~:(first-name,last-name)');

echo "Welcome ".$user['first-name'];