# Upgrade

This document explains how you upgrade from one version to another. 

## Upgrade from 0.7.2 to 1.0

### Changes

* We do not longer require `php-http/message`. You have to make sure to put that in your own composer.json.

## Upgrade from 0.7.1 to 0.7.2

### Changes

* Using `php-http/discovery:1.0`
* Code style changes.

## Upgrade from 0.7.0 to 0.7.1

### Changes

* Using `php-http/discovery:0.9` which makes Puli optional
* Using new URL's to LinkedIn API so users are provided with the new authentication UX. (Thanks to @mbarwick83)

## Upgrade from 0.6 to 0.7

### Changes

* Introduced PHP-HTTP and PSR-7 messages
* Added constructor argument for responseDataType
* Added setResponseDataType()
* Moved authentication functions to `Authenticator` class  

To make sure you can upgrade you need to install a HTTP adapter.

```bash
php composer.phar require php-http/guzzle6-adapter
```

### BC breaks

* Removed `LinkedIn::setRequest` in favor of `LinkedIn::setHttpAdapter`
* Removed `LinkedIn::getAppSecret` and `LinkedIn::getAppId` 
* Removed `LinkedIn::getUser`
* Removed `LinkedInApiException` in favor of `LinkedInException`, `InvalidArgumentException` and `LinkedInTransferException` 
* Removed `LinkedIn::getLastHeaders` in favor of `LinkedIn::getLastResponse`
* Made the public functions `LinkedIn::getResponseDataType` and `LinkedIn::getFormat` protected

## Upgrade from 0.5 to 0.6

### Changes

* When exchanging the code for an access token we are now using the post body instead of query parameters
* Better error handling when exchange from code to access token fails

### BC breaks

There are a few minor BC breaks. We removed the functions below: 

* `LinkedIn::getUserId`, use `LinkedIn::getUser` instead
* `AccessToken::constructFromJson`, Use the constructor instead. 

## Upgrade from 0.4 to 0.5

### Changed signature of `LinkedIn::api`

The signature of `LinkedIn::api` has changed to be more easy to work with. 
```php
// Version 0.4
public function api($resource, array $urlParams=array(), $method='GET', $postParams=array())

// Version 0.5
public function api($method, $resource, array $options=array())
```

This means that you have to modify your calls to: 
```php
// Version 0.5
$options = array('query'=>$urlParams, 'body'=>$postParams);
$linkedIn->api('POST', $resource, $options)
```
See the Readme about more options to the API function. 

### Must inject IlluminateSessionStorage

We have removed the protected `LinkedIn::init` function. That means if you were using `IlluminateSessionStorage` you have
to make a minor adjustment to your code. 

```php
// Version 0.4
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');

// Version 0.5
$linkedIn=new Happyr\LinkedIn\LinkedIn('app_id', 'app_secret');
$linkedIn->setStorage(new IlluminateSessionStorage());
```

If you don't know about `IlluminateSessionStorage` you are probably good ignoring this. 

### Default format 

The default format when communicating with LinkedIn API is changed to  json. 

### Updated RequestInterface

The `RequestInterface::send` was updated with a new signature. We did also introduce `RequestInterface::getHeadersFromLastResponse`. 
