# LinkedIn API client in PHP

[![Build Status](https://travis-ci.org/Happyr/LinkedIn-API-client.svg?branch=master)](https://travis-ci.org/Happyr/LinkedIn-API-client)
[![Coverage Status](https://img.shields.io/coveralls/Happyr/LinkedIn-API-client.svg)](https://coveralls.io/r/Happyr/LinkedIn-API-client?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/44c425af-90f6-4c25-b789-4ece28b01a2b/mini.png)](https://insight.sensiolabs.com/projects/44c425af-90f6-4c25-b789-4ece28b01a2b)
[![Latest Stable Version](https://poser.pugx.org/happyr/linkedin-api-client/v/stable.svg)](https://packagist.org/packages/happyr/linkedin-api-client)
[![Monthly Downloads](https://poser.pugx.org/happyr/linkedin-api-client/d/monthly.png)](https://packagist.org/packages/happyr/linkedin-api-client)

A PHP library to handle authentication and communication with LinkedIn API. The library/SDK helps you to get an access
token and when authenticated it helps you to send API requests. You will not get *everything* for free though... You
have to read the [LinkedIn documentation][api-doc-core] to understand how you should query the API. 

To get an overview what this library actually is doing for you. Take a look at the authentication page from
the [API docs][api-doc-authentication].

## Features

Here is a list of features that might convince you to choose this LinkedIn client over some of our competitors'.

* Flexible and easy to extend
* Developed with modern PHP standards
* Not developed for a specific framework. 
* Handles the authentication process
* Respects the CSRF protection

## Installation

Install the library with Composer. At the time of writing we depend on `php-http/httplug` which is in a beta version. 
If you have the default composer stability level (`min-stability: stable`) you need to require the beta package as well. 

```bash
php composer.phar require happyr/linkedin-api-client:dev-master php-http/httplug:v1.0.0-beta
```

You do also need to choose what library to use when you are sending http messages. Consult the
[php-http/client-implementation](https://packagist.org/providers/php-http/client-implementation) virtual package to
find clients to use. For more information about virtual packages please refer to 
[Httplug](http://docs.php-http.org/en/latest/httplug/users.html). Example:

```bash
php composer.phar require php-http/guzzle6-adapter:dev-master
```

If you are updating form a previous version make sure to read [the upgrade documentation](Upgrade.md).

### Puli

If you run in to issues with [Puli](http://docs.puli.io/en/latest/) when installing or gets the error *No factories found*. 
Make sure you have installed [Puli cli](http://docs.puli.io/en/latest/installation.html) or try to require a lower version
of `php-http/discovery`. 

```bash
php composer.phar require php-http/discovery:^0.5
```

## Usage

In order to use this API client (or any other LinkedIn clients) you have to [register your application][register-app]
with LinkedIn to receive an API key. Once you've registered your LinkedIn app, you will be provided with
an *API Key* and *Secret Key*.

### LinkedIn login

This example below is showing how to login with LinkedIn.

```php 
<?php

/**
 * This demonstrates how to authenticate with LinkedIn and send api requests
 */

/*
 * First you need to make sure you've used composers auto load. You have is probably 
 * already done this before. You usually don't bother..
 */
//require_once "vendor/autoload.php";

$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');

if ($linkedIn->isAuthenticated()) {
    //we know that the user is authenticated now. Start query the API
    $user=$linkedIn->get('v1/people/~:(firstName,lastName)');
    echo "Welcome ".$user['firstName'];

    exit();
} elseif ($linkedIn->hasError()) {
    echo "User canceled the login.";
    exit();
}

//if not authenticated
$url = $linkedIn->getLoginUrl();
echo "<a href='$url'>Login with LinkedIn</a>";

```

### How to post on LinkedIn wall

The example below shows how you can post on a users wall. The access token is fetched from the database. 

```php
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');
$linkedIn->setAccessToken('access_token_from_db');

$options = array('json'=>
    array(
        'comment' => 'Im testing Happyr LinkedIn client! https://github.com/Happyr/LinkedIn-API-client',
        'visibility' => array(
            'code' => 'anyone'
        )
    )
);

$result = $linkedIn->post('v1/people/~/shares', $options);

var_dump($result);

// Prints: 
// array (size=2)
//   'updateKey' => string 'UPDATE-01234567-0123456789012345678' (length=35)
//   'updateUrl' => string 'https://www.linkedin.com/updates?discuss=&scope=01234567&stype=M&topic=0123456789012345678&type=U&a=mVKU' (length=104)

```

You may of course do the same in xml. Use the following options array.
```php
$options = array(
'format' => 'xml',
'body' => '<share>
 <comment>Im testing Happyr LinkedIn client! https://github.com/Happyr/LinkedIn-API-client</comment>
 <visibility>
   <code>anyone</code>
 </visibility>
</share>');
```

## Configuration

### The api options

The third parameter of `LinkedIn::api` is an array with options. Below is a table of array keys that you may use. 

| Option name | Description
| ----------- | -----------
| body | The body of a HTTP request. Put your xml string here. 
| format | Set this to 'json', 'xml' or 'simple_xml' to override the default value.
| headers | This is HTTP headers to the request
| json | This is an array with json data that will be encoded to a json string. Using this option you do need to specify a format. 
| response_data_type | To override the response format for one request 
| query | This is an array with query parameters



### Changing request format

The default format when communicating with LinkedIn API is json. You can let the API do `json_encode` for you. 
The following code shows you how. 

```php
$body = array(
    'comment' => 'Im testing Happyr LinkedIn client! https://github.com/Happyr/LinkedIn-API-client',
    'visibility' => array('code' => 'anyone')
);

$linkedIn->post('v1/people/~/shares', array('json'=>$body));
$linkedIn->post('v1/people/~/shares', array('body'=>json_encode($body)));
```

When using `array('json'=>$body)` as option the format will always be `json`. You can change the request format in three ways.

```php
// By constructor argument
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret', 'xml');

// By setter
$linkedIn->setFormat('xml');

// Set format for just one request
$linkedIn->post('v1/people/~/shares', array('format'=>'xml', 'body'=>$body));
```


### Understanding response data type

The data type returned from `LinkedIn::api` can be configured. You may use the forth construtor argument, the
`LinkedIn::setResponseDataType` or as an option for `LinkedIn::api`

```php
// By constructor argument
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret', 'json', 'array');

// By setter
$linkedIn->setResponseDataType('simple_xml');

// Set format for just one request
$linkedIn->get('v1/people/~:(firstName,lastName)', array('response_data_type'=>'psr7'));

```

Below is a table that specifies what the possible return data types are when you call `LinkedIn::api`.

| Type | Description
| ------ | ------------
| array | An assosiative array. This can only be used with the `json` format.
| simple_xml | A SimpleXMLElement. See [PHP manual](http://php.net/manual/en/class.simplexmlelement.php). This can only be used with the `xml` format.
| psr7 | A PSR7 response.
| stream | A file stream.
| string | A plain old string.


### Use different Session classes

You might want to use an other storage than the default `SessionStorage`. If you are using Laravel
you are more likely to inject the `IlluminateSessionStorage`.  
```php
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');
$linkedIn->setStorage(new IlluminateSessionStorage());
```

You can inject any class implementing `DataStorageInterface`. You can also inject different `UrlGenerator` classes.

### Using different scopes

If you want to define special scopes when you authenticate the user you should specify them when you are generating the 
login url. If you don't specify scopes LinkedIn will use the default scopes that you have configured for the app.  

```php
$scope = 'r_fullprofile,r_emailaddress,w_share';
//or 
$scope = array('rw_groups', 'r_contactinfo', 'r_fullprofile', 'w_messages');

$url = $linkedIn->getLoginUrl(array('scope'=>$scope));
echo "<a href='$url'>Login with LinkedIn</a>";
```

## Framework integration

If you want an easier integration with a framwork you may want to check out these repositories: 

* [HappyrLinkedInBundle](https://github.com/Happyr/LinkedInBundle) for Symfony
* [Laravel-Linkedin by mauri870](https://github.com/mauri870/laravel-linkedin) for Laravel 5.1


[register-app]: https://www.linkedin.com/secure/developer
[linkedin-code-samples]: https://developer.linkedin.com/documents/code-samples
[api-doc-authentication]: https://developer.linkedin.com/documents/authentication
[api-doc-core]: https://developer.linkedin.com/core-concepts
