# LinkedIn API client in PHP

[![Build Status](https://travis-ci.org/Happyr/LinkedIn-API-client.svg?branch=master)](https://travis-ci.org/Happyr/LinkedIn-API-client)
[![Coverage Status](https://img.shields.io/coveralls/Happyr/LinkedIn-API-client.svg)](https://coveralls.io/r/Happyr/LinkedIn-API-client?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/44c425af-90f6-4c25-b789-4ece28b01a2b/mini.png)](https://insight.sensiolabs.com/projects/44c425af-90f6-4c25-b789-4ece28b01a2b)
[![Latest Stable Version](https://poser.pugx.org/happyr/linkedin-api-client/v/stable.svg)](https://packagist.org/packages/happyr/linkedin-api-client)
[![Monthly Downloads](https://poser.pugx.org/happyr/linkedin-api-client/d/monthly.png)](https://packagist.org/packages/happyr/linkedin-api-client)

A PHP library to handle authentication and communication with LinkedIn API. The library/SDK helps you to get an access
token and when authenticated it helps you to send API requests. You will not get *everything* for free though... You
have to read the [LinkedIn documentation][api-doc-core] to understand how you should query the API. We just help you
with the boring stuff.

To get an overview what this library actually is doing for you. Take a look at the authentication page from
the [API docs][api-doc-authentication].

## Features

Here is a list of features that might convince you to choose this LinkedIn client over some of our competitors'.

* Easy to extend
* Simple to implement
* Object oriented 
* Using composer.json
* Handles CSRF protection for you
* Not developed for a specific framework. 
* More than 85% test coverage.
* 580 lines of code, 540 lines of comments.

## Installation

Install it with Composer!

```js
// composer.json
{
    // ...
    require: {
        // ...
        "happyr/linkedin-api-client": "dev-master",
    }
}
```

Then, you can install the new dependencies by running the ``composer update``
command from the directory where your ``composer.json`` file is located:

## Usage

In order to use this API client (or any other LinkedIn clients) you have to [register your application][register-app]
with LinkedIn to receive an API key. Once you've registered your LinkedIn app, you will be provided with
an *API Key* and *Secret Key*.

This example below is a nicer way of connecting to LinkedIn compared to [their code samples][linkedin-code-samples].

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
    $user=$linkedIn->api('v1/people/~:(firstName,lastName)');
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

You can easily implement your own session storage device and use it with this library. Just make sure your session
storage class implements the `\Happyr\LinkedIn\Storage\DataStorageInterface` interface (or write an adapter that
uses your class and implements that interface as shown below).

```php
<?php

/**
 * First write a class or an adapter that implements DataStorageInterface
 */
use \Happyr\LinkedIn\Storage\DataStorageInterface;

class CacheManagerHappyrLinkedInAdapter implements DataStorageInterface
{
	public function __construct($cacheManager) {}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key
	 * @param array $value
	 * @return void
	 */
	public function set($key, $value) {}

	/**
	 * {@inheritDoc}
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get($key, $default = false) {}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $key
	 * @return void
	 */
	public function clear($key) {}

	/**
	 * {@inheritDoc}
	 *
	 * @return void
	 */
	public function clearAll() {}
}

/**
 * Now you can use your custom class as the session storage for this library
 */
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');
// The line of code below is how you tell the library to use your custom session storage
$linkedIn->setStorage(new CacheManagerHappyrLinkedInAdapter());

```

### Framework integration

See how I integrated this with [Symfony2](docs/symfony.md).


[register-app]: https://www.linkedin.com/secure/developer
[linkedin-code-samples]: https://developer.linkedin.com/documents/code-samples
[api-doc-authentication]: https://developer.linkedin.com/documents/authentication
[api-doc-core]: https://developer.linkedin.com/core-concepts
