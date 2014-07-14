# LinkedIn API client in PHP

[![Build Status](https://travis-ci.org/HappyR/LinkedIn-API-client.svg?branch=master)](https://travis-ci.org/HappyR/LinkedIn-API-client)
[![Coverage Status](https://img.shields.io/coveralls/HappyR/LinkedIn-API-client.svg)](https://coveralls.io/r/HappyR/LinkedIn-API-client?branch=master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/44c425af-90f6-4c25-b789-4ece28b01a2b/mini.png)](https://insight.sensiolabs.com/projects/44c425af-90f6-4c25-b789-4ece28b01a2b)
[![Latest Stable Version](https://poser.pugx.org/happyr/linkedin-api-client/v/stable.svg)](https://packagist.org/packages/happyr/linkedin-api-client)
[![Monthly Downloads](https://poser.pugx.org/happyr/linkedin-api-client/d/monthly.png)](https://packagist.org/packages/happyr/linkedin-api-client)

A PHP library to handle authentication and communication with LinkedIn API. The library/SDK helps you to get an access
token and when authenticated it helps you to send API requests. You will nog get *everything* for free though... You
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
//require_once "../vendor/autoload.php"

$linkedIn=new HappyR\LinkedIn\LinkedIn('app_id', 'app_secret');

if ($linkedIn->isAuthenticated()) {
    //we know that the user is authenticated now. Start query the API
    $user=$linkedIn->api('v1/people/~:(firstName,lastName)');
    echo "Welcome ".$user['firstName'];

    exit();
} elseif ($linkedIn->hasError()) {
    echo "User canceled the login."
    exit();
}

//if not authenticated
$url = $linkedIn->getLoginUrl();
echo "<a href='$url'>Login with LinkedIn</a>";

```


### Integrating with Symfony2

It is very easy to integrate this LinkedIn client with Symfony2. I created a service that extended
HappyR\LinkedIn\LinkedIn and a controller to enable LinkedIn authentication.

```php
<?php

namespace Acme\LinkedInBundle\Services;

use HappyR\LinkedIn\LinkedIn;

/**
 * Extends the LinkedIn class 
 */
class LinkedInService extends LinkedIn
{
    /**
     * @var string scope
     *
     */
    protected $scope;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->scope = $config['scope'];

        parent::__construct($config['appId'], $config['secret']);
    }

    /**
     * Set the scope 
     *
     * @param array $params
     *
     * @return string
     */
    public function getLoginUrl($params = array())
    {
        if (!isset($params['scope']) || $params['scope'] == "") {
            $params['scope'] = $this->scope;
        }

        return parent::getLoginUrl($params);
    }

    /**
     * I overrided this function because I want the default user array to include email-address
     */
    protected function getUserFromAccessToken() {
        try {
            return $this->api('/v1/people/~:(id,firstName,lastName,headline,email-address)');
        } catch (LinkedInApiException $e) {
            return null;
        }
    }
}
```

```php
<?php

namespace Acme\LinkedInBundle\Controller;

/* --- */

class LinkedInController extends Controller
{
    /**
     * Login a user with linkedin
     *
     * @Route("/linkedin-login", name="_public_linkedin_login")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function loginAction()
    {
        $linkedIn=$this->get('linkedin');

        if ($linkedIn->isAuthenticated()) {
            $data=$linkedIn->getUser();

            /*
             * The user is authenticated with linkedIn. I have to check in my user DB if 
             * I have seen this user before or not. 
             * 
             * I could now: 
             *  - Register the user
             *  - Login the user
             *  - Show some LinkedIn data
             */

        }

        $redirectUrl=$this->generateUrl('_public_linkedin_login', array(), true);
        $url=$linkedIn->getLoginUrl(array('redirect_uri'=>$redirectUrl));

        return $this->redirect($url);
    }
}
```

[register-app]: https://www.linkedin.com/secure/developer
[linkedin-code-samples]: https://developer.linkedin.com/documents/code-samples
[api-doc-authentication]: https://developer.linkedin.com/documents/authentication
[api-doc-core]: https://developer.linkedin.com/core-concepts
