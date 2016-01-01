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

Install it with Composer.

```bash
php composer.phar require happyr/linkedin-api-client:dev-master
```

You do also need to choose what library to use when you are sending http messages. Consult the
[php-http/adapter-implementation](https://packagist.org/providers/php-http/adapter-implementation) virtual package to
find adapters to use. For more information about virtual packages please refer to 
[Httplug](http://docs.httplug.io/en/latest/virtual-package/). Example:

```bash
php composer.phar require php-http/guzzle5-adapter:dev-master
```

If you are updating form a previous version make sure to read [the upgrade documentation](Upgrade.md).

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

The third parameter of `LinkedIn::api` is an array with options. They will eventually be past to a Request client but 
before that we do some modifications. Below is a table of array keys that you may use. 

| Option name | Description
| ----------- | -----------
| body | The body of a HTTP request. Put your xml string here. 
| format | Set this to 'json', 'xml' or 'simple_xml' to override the default value.
| headers | This is HTTP headers to the request
| json | This is an array with json data that will be encoded to a json string. Using this option you do need to specify a format. 
| response_data_type | To override the response format for one request 
| query | This is an array with query parameters


If you are using the `GuzzleRequest` (default) you may want to have a look at [its documentation](http://docs.guzzlephp.org/en/latest/clients.html?highlight=format#request-options)
to find out what more options that are available. 

### Changing request format

The default format when communicating with LinkedIn API is json. This means that you will get an array back as a response when you call `LinkedIn::api`. It is easy to change the format.

```php
// By constructor argument
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret', 'xml');

// By setter
$linkedIn->setFormat('xml');

// Set format for just one request
$linkedIn->get('v1/people/~:(firstName,lastName)', array('format'=>'xml'));
```

There is one exception to the *format* option: When you specifying $options['json'=>...] then the format will always be json.

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
