<?php

namespace HappyR\LinkedIn;

use HappyR\LinkedIn\Exceptions\LinkedInApiException;
use HappyR\LinkedIn\Http\UrlGenerator;

/**
 * Class LinkedIn
 *
 * @author Tobias Nyholm
 *
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
     * The ID of the Facebook user, or 0 if the user is logged out.
     *
     * @var integer
     */
    protected $userId;

    /**
     * A CSRF state variable to assist in the defense against CSRF attacks.
     */
    protected $state;

    /**
     * The OAuth access token received in exchange for a valid authorization
     * code.  null means the access token has yet to be determined.
     *
     * @var string
     */
    protected $accessToken = null;

    /**
     * @var \HappyR\LinkedIn\Storage\DataStorage storage
     *
     */
    protected $storage;

    /**
     * @var string storageClass
     *
     * The class that handles the storage. Override this value with a subclass
     */
    protected $storageClass = 'HappyR\LinkedIn\Storage\SessionStorage';

    /**
     * @var \HappyR\LinkedIn\Http\UrlGenerator urlGenerator
     *
     */
    protected $urlGenerator;

    /**
     * @var \HappyR\LinkedIn\Http\RequestInterface request
     *
     */
    protected $request;

    /**
     * @var string storageClass
     *
     * The class that handles the storage. Override this value with a subclass
     */
    protected $requestClass = 'HappyR\LinkedIn\Http\Request';

    /**
     * @param $appId
     * @param $appSecret
     */
    public function __construct($appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;

        $this->urlGenerator = new UrlGenerator();

        $class=$this->requestClass;
        $this->request=new $class();

        $class=$this->storageClass;
        $this->storage=new $class();
    }

    /**
     * Is the current user authenticated?
     *
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->getUserId() != null;
    }

    public function getUserId()
    {
        if ($this->userId !== null) {
            // we've already determined this and cached the value.
            return $this->userId;
        }

        return $this->user = $this->getUserFromAvailableData();
    }

    /**
     * Determines the connected user by first examining any signed
     * requests, then considering an authorization code, and then
     * falling back to any persistent store storing the user.
     *
     * @return integer The id of the connected Facebook user,
     *                 or 0 if no such user exists.
     */
    protected function  getUserFromAvailableData()
    {
        $user = $this->storage->get('user_id', null);
        $persisted_access_token = $this->storage->get('access_token');

        // use access_token to fetch user id if we have a user access_token, or if
        // the cached access token has changed.
        $access_token = $this->getAccessToken();

        if ($access_token  && !($user && $persisted_access_token == $access_token)) {
            $user = $this->getUserFromAccessToken();
            if ($user) {
                $this->storage->set('user_id', $user);
            } else {
                $this->storage->clearAll();
            }
        }

        return $user;
    }

    /**
     * Retrieves the UID with the understanding that
     * $this->accessToken has already been set and is
     * seemingly legitimate.  It relies on Facebook's Graph API
     * to retrieve user information and then extract
     * the user ID.
     *
     * @return integer Returns the UID of the Facebook user, or 0
     *                 if the Facebook user could not be determined.
     */
    protected function getUserFromAccessToken() {
        try {
            $userInfo = $this->api('/v1/people/~:(id)');
            return $userInfo['id'];
        } catch (LinkedInApiException $e) {
            return null;
        }
    }

    /**
     * Get the authorization code from the query parameters, if it exists,
     * and otherwise return false to signal no authorization code was
     * discoverable.
     *
     * @return mixed The authorization code, or false if the authorization
     *               code could not be determined.
     */
    protected function getCode() {
        if (isset($_REQUEST['code'])) {
            $state = $this->getState();
            //if state exists in session and in request and if they are equal
            if (null !== $state && isset($_REQUEST['state']) && $state === $_REQUEST['state']) {
                // CSRF state has done its job, so clear it
                $this->state = null;
                $this->storage->clear('state');
                return $_REQUEST['code'];
            } else {
                throw new LinkedInApiException('CSRF state token does not match one provided.');
                return false;
            }
        }

        return false;
    }

    /**
     * Determines the access token that should be used for API calls.
     * The first time this is called, $this->accessToken is set equal
     * to either a valid user access token, or it's set to the application
     * access token if a valid user access token wasn't available.  Subsequent
     * calls return whatever the first call returned.
     *
     * @return string The access token
     */
    public function getAccessToken() {
        if ($this->accessToken !== null) {
            // we've done this already and cached it.  Just return.
            return $this->accessToken;
        }

        // first establish access token to be the application
        // access token, in case we navigate to the /oauth/access_token
        // endpoint, where SOME access token is required.
        //$this->setAccessToken('temp_token');
        $user_access_token = $this->getUserAccessToken();
        if ($user_access_token) {
            $this->setAccessToken($user_access_token);
        }

        return $this->accessToken;
    }

    /**
     *
     * @param string $accessToken
     *
     * @return $this
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }



    /**
     * Determines and returns the user access token, first using
     * the signed request if present, and then falling back on
     * the authorization code if present.  The intent is to
     * return a valid user access token, or false if one is determined
     * to not be available.
     *
     * @return string A valid user access token, or false if one
     *                could not be determined.
     */
    protected function getUserAccessToken() {

        $code = $this->getCode();
        if ($code && $code != $this->storage->get('code')) {
            $access_token = $this->getAccessTokenFromCode($code);
            if ($access_token) {
                $this->storage->set('code', $code);
                $this->storage->set('access_token', $access_token);
                return $access_token;
            }

            // code was bogus, so everything based on it should be invalidated.
            $this->storage->clearAll();
            throw new LinkedInApiException('Could not get access token');
        }

        // as a fallback, just return whatever is in the persistent
        // store, knowing nothing explicit (signed request, authorization
        // code, etc.) was present to shadow it (or we saw a code in $_REQUEST,
        // but it's the same as what's in the persistent store)
        return $this->storage->get('access_token');
    }

    /**
     * Retrieves an access token for the given authorization code
     * (previously generated from www.facebook.com on behalf of
     * a specific user).  The authorization code is sent to graph.facebook.com
     * and a legitimate access token is generated provided the access token
     * and the user for which it was generated all match, and the user is
     * either logged in to Facebook or has granted an offline access permission.
     *
     * @param string $code An authorization code.
     * @return mixed An access token exchanged for the authorization code, or
     *               false if an access token could not be generated.
     */
    protected function getAccessTokenFromCode($code, $redirect_uri = null) {
        if (empty($code)) {
            return false;
        }

        if ($redirect_uri === null) {
            $redirect_uri = $this->urlGenerator->getCurrentUrl();
        }

        try {
            // need to circumvent json_decode by calling _oauthRequest
            // directly, since response isn't JSON format.
            $access_token_response = $this->request->create(
                $this->urlGenerator->getUrl('www', 'uas/oauth2/accessToken',
                array(
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirect_uri,
                    'client_id' => $this->getAppId(),
                    'client_secret' => $this->getAppSecret(),
                ))
                , array(), 'POST'
            );



        } catch (LinkedInApiException $e) {
            // most likely that user very recently revoked authorization.
            // In any event, we don't have an access token, so say so.
            return false;
        }

        if (empty($access_token_response)) {
            return false;
        }

        $response_params = json_decode($access_token_response, true);
        if (!isset($response_params['access_token'])) {
            return false;
        }

        return $response_params['access_token'];
    }

    /**
     * Make an API call
     *
     * @param $resource
     * @param array $params
     *
     * @return mixed
     */
    public function api($resource, array $params=array()) {
        if (!isset($params['oauth2_access_token'])) {
            $params['oauth2_access_token'] = $this->getAccessToken();
        }
        if (!isset($params['format'])) {
            $params['format'] = 'json';
        }

        $url=$this->urlGenerator->getUrl('api', $resource, $params);
        $result= $this->request->create($url);

        return json_decode($result, true);
    }


    /**
     * Get a Login URL for use with redirects. By default, full page redirect is
     * assumed. If you are using the generated URL with a window.open() call in
     * JavaScript, you can pass in display=popup as part of the $params.
     *
     * The parameters:
     * - redirect_uri: the url to go to after a successful login
     * - scope: comma separated list of requested extended perms
     *
     * @param array $params Provide custom parameters
     * @return string The URL for the login flow
     */
    public function getLoginUrl($params=array()) {
        $this->establishCSRFTokenState();
        $currentUrl = $this->urlGenerator->getCurrentUrl();

        // if 'scope' is passed as an array, convert to comma separated list
        $scopeParams = isset($params['scope']) ? $params['scope'] : null;
        if ($scopeParams && is_array($scopeParams)) {
            $params['scope'] = implode(' ', $scopeParams);
        }

        return $this->urlGenerator->getUrl(
            'www',
            'uas/oauth2/authorization',
            array_merge(
                array(
                    'response_type'=>'code',
                    'client_id' => $this->getAppId(),
                    'redirect_uri' => $currentUrl, // possibly overwritten
                    'state' => $this->state,
                ),
                $params
            ));
    }

    /**
     * Lays down a CSRF state token for this process.
     *
     * @return void
     */
    protected function establishCSRFTokenState()
    {
        if ($this->state === null) {
            $this->state = md5(uniqid(mt_rand(), true));
            $this->storage->set('state', $this->state);
        }
    }

    /**
     * Get the state
     *
     *
     * @return mixed
     */
    protected function getState()
    {
        if ($this->state === null) {
            $this->state = $this->storage->get('state', null);
        }

        return $this->state;
    }



    /**
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->appId;
    }

    /**
     *
     * @return string
     */
    public function getAppSecret()
    {
        return $this->appSecret;
    }


} 