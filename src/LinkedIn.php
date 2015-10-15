<?php

namespace Happyr\LinkedIn;

use Happyr\LinkedIn\Exceptions\LinkedInApiException;
use Happyr\LinkedIn\Exceptions\LoginError;
use Happyr\LinkedIn\Http\GuzzleRequest;
use Happyr\LinkedIn\Http\RequestInterface;
use Happyr\LinkedIn\Http\UrlGenerator;
use Happyr\LinkedIn\Http\UrlGeneratorInterface;
use Happyr\LinkedIn\Storage\DataStorageInterface;
use Happyr\LinkedIn\Storage\SessionStorage;

/**
 * Class LinkedIn lets you talk to LinkedIn api.
 *
 * When a new user arrives and want to authenticate here is whats happens:
 * 1. You redirect him to whatever url getLoginUrl() returns.
 * 2. The user logs in on www.linkedin.com and authorize your application.
 * 3. The user returns to your site with a *code* in the the $_REQUEST.
 * 4. You call getUser()
 * 5. getUser() needs a access token so it calls getAccessToken()
 * 6. We don't got an access token (only a *code*). So getAccessToken() calls fetchNewAccessToken()
 * 7. fetchNewAccessToken() gets the *code* and calls getAccessTokenFromCode()
 * 8. getAccessTokenFromCode() makes a request to www.linkedin.com and exchanges the *code* for an access token
 * 9. With a valid access token we can query the api for the user
 * 10. When you make a second request to the api you skip the authorization (1-3) and
 *     the "*code* for access token exchange" (6-8).
 *
 * @author Tobias Nyholm
 */
class LinkedIn
{
    /**
     * The Application ID.
     *
     * @var string
     */
    protected $appId;

    /**
     * The Application App Secret.
     *
     * @var string
     */
    protected $appSecret;

    /**
     * An array with default user stuff.
     *
     * @var mixed
     */
    protected $user;

    /**
     * A CSRF state variable to assist in the defense against CSRF attacks.
     */
    protected $state;

    /**
     * The OAuth access token received in exchange for a valid authorization
     * code.  null means the access token has yet to be determined.
     *
     * @var AccessToken
     */
    protected $accessToken = null;

    /**
     * @var DataStorageInterface storage
     */
    private $storage;

    /**
     * @var UrlGeneratorInterface urlGenerator
     */
    private $urlGenerator;

    /**
     * @var \Happyr\LinkedIn\Http\RequestInterface request
     */
    private $request;

    /**
     * @var string format
     */
    private $format;

    /**
     * Constructor.
     *
     * @param string $appId
     * @param string $appSecret
     * @param string $format    'json', 'xml' or 'simple_xml'
     */
    public function __construct($appId, $appSecret, $format = 'json')
    {
        //save app stuff
        $this->appId = $appId;
        $this->appSecret = $appSecret;

        $this->format = $format;
    }

    /**
     * Is the current user authenticated?
     *
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->getUser() !== null;
    }

    /**
     * Make an API call.
     *
     * $linkedIn->api('GET', '/v1/people/~:(id,firstName,lastName,headline)');
     *
     * @param string $method   This is the HTTP verb
     * @param string $resource everything after the domain in the URL.
     * @param array  $options  [optional] This is the options you may pass to the request. You might be interested
     *                         in setting values for 'query', 'headers', 'body' or 'json'. See the readme for more.
     *
     * @return array|\SimpleXMLElement|string What the function return depends on what format is used. If 'json'
     *                                        you will get an array back. If 'xml' you will get a string. If you are setting the option 'simple_xml' to true
     *                                        you will get a \SimpleXmlElement back.
     */
    public function api($method, $resource, array $options = array())
    {
        // Add access token to the headers
        $options['headers']['Authorization'] = sprintf('Bearer %s', (string) $this->getAccessToken());

        // Do logic and adjustments to the options
        $this->filterRequestOption($options);

        // Generate an url
        $url = $this->getUrlGenerator()->getUrl('api', $resource, isset($options['query']) ? $options['query'] : array());
        unset($options['query']);

        // $method that url
        $result = $this->getRequest()->send($method, $url, $options);

        return $result;
    }

    /**
     * See docs for LinkedIn::api.
     *
     * @param string $resource
     * @param array  $options
     *
     * @return array|\SimpleXMLElement|string
     */
    public function get($resource, array $options = array())
    {
        return $this->api('GET', $resource, $options);
    }

    /**
     * See docs for LinkedIn::api.
     *
     * @param string $resource
     * @param array  $options
     *
     * @return array|\SimpleXMLElement|string
     */
    public function post($resource, array $options = array())
    {
        return $this->api('POST', $resource, $options);
    }

    /**
     * Get a Login URL for use with redirects. By default, full page redirect is
     * assumed. If you are using the generated URL with a window.open() call in
     * JavaScript, you can pass in display=popup as part of the $params.
     *
     * The parameters:
     * - redirect_uri: the url to go to after a successful login
     * - scope: comma (or space) separated list of requested extended permissions
     *
     * @param array $params Provide custom parameters
     *
     * @return string The URL for the login flow
     */
    public function getLoginUrl($params = array(), $redirect_uri)
    {
        $this->establishCSRFTokenState();
        if(!empty($redirect_uri)) {
            $currentUrl = $redirect_uri;
        } else {
            $currentUrl = $this->getUrlGenerator()->getCurrentUrl();
        }

        // if 'scope' is passed as an array, convert to space separated list
        $scopeParams = isset($params['scope']) ? $params['scope'] : null;
        if ($scopeParams) {
            //if scope is an array
            if (is_array($scopeParams)) {
                $params['scope'] = implode(' ', $scopeParams);
            } elseif (is_string($scopeParams)) {
                //if scope is a string with ',' => make it to an array
                $params['scope'] = str_replace(',', ' ', $scopeParams);
            }
        }

        return $this->getUrlGenerator()->getUrl(
            'www',
            'uas/oauth2/authorization',
            array_merge(
                array(
                    'response_type' => 'code',
                    'client_id' => $this->getAppId(),
                    'redirect_uri' => $currentUrl, // possibly overwritten
                    'state' => $this->getState(),
                ),
                $params
            )
        );
    }

    /**
     * Get the user response.
     *
     * @return mixed|null
     */
    public function getUser()
    {
        if ($this->user === null) {
            // if we have not already determined this and cached the result.
            $this->user = $this->getUserFromAvailableData();
        }

        return $this->user;
    }

    /**
     *  Determines the connected user by first considering an authorization code, and then
     * falling back to any persistent store storing the user.
     *
     * @return mixed|null get an user array or null
     */
    protected function getUserFromAvailableData()
    {
        $storage = $this->getStorage();

        //get saved values
        $user = $storage->get('user', null);
        $persistedAccessToken = $storage->get('access_token');

        $accessToken = $this->getAccessToken();

        /*
         * This is true if both statements are true:
         * 1: We got an access token
         * 2: The access token has changed or if we don't got a user.
         */
        if ($accessToken && !($user !== null && $persistedAccessToken == $accessToken)) {
            $user = $this->getUserFromAccessToken();
            if ($user !== null) {
                $storage->set('user', $user);
            } else {
                $storage->clearAll();
            }
        }

        return $user;
    }

    /**
     * Retrieves the user array with the understanding that
     * $this->accessToken has already been set and is
     * seemingly legitimate.
     *
     * You should override this function if you want to change the default user array
     *
     * @return mixed|null null if we could not get user data
     */
    protected function getUserFromAccessToken()
    {
        try {
            return $this->api('GET', '/v1/people/~:(id,firstName,lastName,headline)');
        } catch (LinkedInApiException $e) {
            return;
        }
    }

    /**
     * Get the authorization code from the query parameters, if it exists,
     * and otherwise return null to signal no authorization code was
     * discoverable.
     *
     * @return string|null The authorization code, or null if the authorization code could not be determined.
     *
     * @throws LinkedInApiException
     */
    protected function getCode()
    {
        $storage = $this->getStorage();

        if (isset($_REQUEST['code'])) {
            if ($storage->get('code') === $_REQUEST['code']) {
                //we have already validated this code
                return;
            }

            //if stored state does not exists
            if (null === $state = $this->getState()) {
                throw new LinkedInApiException('Could not find a stored CSRF state token.');
            }

            //if state exists in the request
            if (!isset($_REQUEST['state'])) {
                throw new LinkedInApiException('Could not find a CSRF state token in the request.');
            }

            //if state exists in session and in request and if they are not equal
            if ($state !== $_REQUEST['state']) {
                throw new LinkedInApiException('The CSRF state token from the request does not match the stored token.');
            }

            // CSRF state has done its job, so clear it
            $this->setState(null);
            $storage->clear('state');

            return $_REQUEST['code'];
        }

        return;
    }

    /**
     * Determines the access token that should be used for API calls.
     *
     *
     * @return AccessToken|null The access token of null if the access token is not found
     */
    public function getAccessToken()
    {
        if ($this->accessToken !== null) {
            // we've done this already and cached it. Just return.
            return $this->accessToken;
        }

        $newAccessToken = $this->fetchNewAccessToken();
        if ($newAccessToken !== null) {
            $this->setAccessToken($newAccessToken);
        }

        // return the new access token or null
        return $this->accessToken;
    }

    /**
     * Determines and returns the user access token using the authorization code. The intent is to
     * return a valid user access token, or null if one is determined to not be available.
     *
     * @return string|AccessToken|null A valid user access token, or null if one could not be determined.
     *
     * @throws LinkedInApiException
     */
    protected function fetchNewAccessToken()
    {
        $storage = $this->getStorage();
        $code = $this->getCode();

        if ($code !== null) {
            $accessToken = $this->getAccessTokenFromCode($code);
            if ($accessToken) {
                $storage->set('code', $code);
                $storage->set('access_token', $accessToken);

                return $accessToken;
            }

            // code was bogus, so everything based on it should be invalidated.
            $storage->clearAll();
            throw new LinkedInApiException('Could not get access token');
        }

        // as a fallback, just return whatever is in the persistent
        // store, knowing nothing explicit (signed request, authorization
        // code, etc.) was present to shadow it (or we saw a code in $_REQUEST,
        // but it's the same as what's in the persistent store)
        return $storage->get('access_token', null);
    }

    /**
     * Retrieves an access token for the given authorization code
     * (previously generated from www.linkedin.com on behalf of
     * a specific user). The authorization code is sent to www.linkedin.com
     * and a legitimate access token is generated provided the access token
     * and the user for which it was generated all match, and the user is
     * either logged in to LinkedIn or has granted an offline access permission.
     *
     * @param string $code        An authorization code.
     * @param string $redirectUri Where the user should be redirected after token is generated.
     *                            Default is the current url
     *
     * @return AccessToken|null An access token exchanged for the authorization code, or
     *                          null if an access token could not be generated.
     */
    protected function getAccessTokenFromCode($code, $redirectUri = null)
    {
        if (empty($code)) {
            return;
        }

        if ($redirectUri === null) {
            $redirectUri = $this->getUrlGenerator()->getCurrentUrl();
        }

        try {
            $response = $this->getRequest()->send(
                'POST',
                $this->getUrlGenerator()->getUrl('www', 'uas/oauth2/accessToken'),
                [
                    'body' => array(
                        'grant_type' => 'authorization_code',
                        'code' => $code,
                        'redirect_uri' => $redirectUri,
                        'client_id' => $this->getAppId(),
                        'client_secret' => $this->getAppSecret(),
                    ),
                ]
            );
        } catch (LinkedInApiException $e) {
            // most likely that user very recently revoked authorization.
            // In any event, we don't have an access token, so say so.
            return;
        }

        if (empty($response)) {
            return;
        }

        $tokenData = array_merge(array('access_token' => null, 'expires_in' => null), $response);
        $token = new AccessToken($tokenData['access_token'], $tokenData['expires_in']);

        if (!$token->hasToken()) {
            return;
        }

        return $token;
    }

    /**
     * Lays down a CSRF state token for this process.
     */
    protected function establishCSRFTokenState()
    {
        if ($this->getState() === null) {
            $this->setState(md5(uniqid(mt_rand(), true)));
            $this->getStorage()->set('state', $this->getState());
        }
    }

    /**
     * Get the state, use this to verify the CSRF token.
     *
     *
     * @return string|null
     */
    protected function getState()
    {
        if ($this->state === null) {
            $this->state = $this->getStorage()->get('state', null);
        }

        return $this->state;
    }

    /**
     * @param $state
     *
     * @return $this
     */
    protected function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get the app id.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     * Get the app secret.
     *
     * @return string
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }

    /**
     * Get the access token.
     *
     * @param string|AccessToken $accessToken
     *
     * @return $this
     */
    public function setAccessToken($accessToken)
    {
        if (!($accessToken instanceof AccessToken)) {
            $accessToken = new AccessToken($accessToken);
        }

        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return $this
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;

        return $this;
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function getUrlGenerator()
    {
        if ($this->urlGenerator === null) {
            $this->urlGenerator = new UrlGenerator();
        }

        return $this->urlGenerator;
    }

    /**
     * @param DataStorageInterface $storage
     *
     * @return $this
     */
    public function setStorage(DataStorageInterface $storage)
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * @return DataStorageInterface
     */
    protected function getStorage()
    {
        if ($this->storage === null) {
            $this->storage = new SessionStorage();
        }

        return $this->storage;
    }

    /**
     * @param RequestInterface $request
     *
     * @return $this
     */
    public function setRequest(RequestInterface $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @return RequestInterface
     */
    protected function getRequest()
    {
        if ($this->request === null) {
            $this->request = new GuzzleRequest();
        }

        return $this->request;
    }

    /**
     * If the user has canceled the login we will return with an error.
     *
     * @return bool
     */
    public function hasError()
    {
        return !empty($_GET['error']);
    }

    /**
     * Returns a LoginError or null.
     *
     * @return LoginError|null
     */
    public function getError()
    {
        if (!$this->hasError()) {
            return;
        }

        return new LoginError($_GET['error'], isset($_GET['error_description']) ? $_GET['error_description'] : null);
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     *
     * @return $this
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get headers from last response.
     *
     * @return array|null
     */
    public function getLastHeaders()
    {
        return $this->getRequest()->getHeadersFromLastResponse();
    }

    /**
     * Modify and filter the request options. Make sure we use the correct query parameters and headers.
     *
     * @param array $options
     */
    protected function filterRequestOption(array &$options)
    {
        if (isset($options['json'])) {
            $options['format'] = 'json';
        } elseif (!isset($options['format'])) {
            $options['format'] = $this->getFormat();
        }

        // Set correct headers for this format
        switch ($options['format']) {
            case 'simple_xml':
                $options['simple_xml'] = true;
                $options['headers']['Content-Type'] = 'text/xml';
                break;
            case 'xml':
                $options['headers']['Content-Type'] = 'text/xml';
                break;
            case 'json':
                $options['headers']['Content-Type'] = 'application/json';
                $options['headers']['x-li-format'] = 'json';
                $options['query']['format'] = 'json';
                break;
            default:
                // Do nothing
        }
        unset($options['format']);
    }
}
