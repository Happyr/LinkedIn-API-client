# Integrating with Symfony2

It is very easy to integrate this LinkedIn client with Symfony2. I created a service that extended
Happyr\LinkedIn\LinkedIn and a controller to enable LinkedIn authentication.

```php
<?php

namespace Acme\LinkedInBundle\Service;

use Happyr\LinkedIn\LinkedIn;

/**
 * Extends the LinkedIn class
 */
class LinkedInService extends LinkedIn
{
    /**
     * @var string scope
     */
    protected $scope;

    /**
     * @param string $appId
     * @param string $secret
     * @param string $scope
     */
    public function __construct($appId, $secret, $scope)
    {
        $this->scope = $scope;
        parent::__construct($appId, $secret);
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
     * I override this function because I want the default user array to include email-address
     */
    protected function getUserFromAccessToken() {
        try {
            return $this->api('GET', '/v1/people/~:(id,firstName,lastName,headline,email-address)');
        } catch (LinkedInApiException $e) {
            return null;
        }
    }
}
```

```yaml

services:
  linkedin:
    class: Acme\LinkedInBundle\Service\LinkedInService
    arguments: ['xxxxappidxxxxx', 'xxxxsecretxxx', 'r_basicprofile,r_emailaddress']
```


```php
<?php

namespace Acme\LinkedInBundle\Controller;

/* --- */

class LinkedInController extends Controller
{
    /**
     * Login a user with LinkedIn
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
