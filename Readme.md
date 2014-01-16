# LinkedIn API client in PHP

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
* Test coverage of 23% (2013-01-15)... FAIL!

If I still haven't convinced you, you might consider these other libraries. This is the top 3 list in my opinion:

* [ashwinks](https://github.com/ashwinks/PHP-LinkedIn-SDK) - Looks good but it's lacking documentation. It does not
handle CSRF protection and it might be difficult to override this API with a subclass to extend it.
* [roel-sluper](https://github.com/roel-sluper/LinkedIn-PHP-API) - No comments but it looks like a strait forward
solution. It has a few bugs in it but hey, I'm not saying that my lib is 100% bug free. This might be a good place
to start if you want to build your own API client.
* [mahmudahsan](https://github.com/mahmudahsan/Linkedin---Simple-integration-for-your-website) - Lots of docs but no
comments, namespaces or objects. It is basically only code samples.


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
with LinkedIn to receive an API key. This unique key helps us identify your application and lets you make API calls.
Once you've registered your LinkedIn app, you will be provided with an *API Key* and *Secret Key*.

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

//if not authenticated
if (!$linkedIn->isAuthenticated()) {
    $url = $linkedIn->getLoginUrl();
    echo "<a href='$url'>Login with LinkedIn</a>";
    exit();
}

//we know that the user is authenticated now
$user=$linkedIn->api('v1/people/~:(firstName,lastName)');

echo "Welcome ".$user['firstName'];
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
             *  - Show some linked in data
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
