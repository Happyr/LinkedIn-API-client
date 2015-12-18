<?php

namespace Happyr\LinkedIn;

use Happyr\LinkedIn\Exceptions\LinkedInApiException;
use Happyr\LinkedIn\Http\GlobalVariableGetter;
use Happyr\LinkedIn\Http\RequestManager;
use Happyr\LinkedIn\Http\ResponseConverter;
use Happyr\LinkedIn\Http\UrlGeneratorInterface;
use Happyr\LinkedIn\Storage\DataStorageInterface;
use Happyr\LinkedIn\Storage\SessionStorage;

class Authenticator
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
     * A CSRF state variable to assist in the defense against CSRF attacks.
     */
    protected $state;

    /**
     * @var DataStorageInterface storage
     */
    private $storage;

    /**
     * @var RequestManager
     */
    private $requestManager;

    /**
     * @param RequestManager $requestManager
     * @param string         $appId
     * @param string         $appSecret
     */
    public function __construct(RequestManager $requestManager, $appId, $appSecret)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->requestManager = $requestManager;
    }

    /**
     * Determines and returns the user access token using the authorization code. The intent is to
     * return a valid access token, or null if one is determined to not be available.
     *
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return AccessToken|null A valid user access token, or null if one could not be determined.
     *
     * @throws LinkedInApiException
     */
    public function fetchNewAccessToken(UrlGeneratorInterface $urlGenerator)
    {
        $storage = $this->getStorage();
        $code = $this->getCode();

        if ($code !== null) {
            $accessToken = $this->getAccessTokenFromCode($urlGenerator, $code);
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
     * @param UrlGeneratorInterface $urlGenerator
     * @param string                $code         An authorization code.
     *
     * @return AccessToken|null An access token exchanged for the authorization code, or
     *                          null if an access token could not be generated.
     */
    private function getAccessTokenFromCode(UrlGeneratorInterface $urlGenerator, $code)
    {
        if (empty($code)) {
            return;
        }

        $redirectUri = $this->getStorage()->get('redirect_url');
        try {
            $url = $urlGenerator->getUrl('www', 'uas/oauth2/accessToken');
            $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
            $body = http_build_query(
                [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                    'client_id' => $this->appId,
                    'client_secret' => $this->appSecret,
                ]
            );

            $response = ResponseConverter::convertToArray($this->requestManager->sendRequest('POST', $url, $headers, $body));
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
     * Get a Login URL for use with redirects. By default, full page redirect is
     * assumed. If you are using the generated URL with a window.open() call in
     * JavaScript, you can pass in display=popup as part of the $params.
     *
     * The parameters:
     * - redirect_uri: the url to go to after a successful login
     * - scope: comma (or space) separated list of requested extended permissions
     *
     * @param UrlGeneratorInterface $urlGenerator
     * @param array                 $options      Provide custom parameters
     *
     * @return string The URL for the login flow
     */
    public function getLoginUrl(UrlGeneratorInterface $urlGenerator, $options = array())
    {
        // Generate a state
        $this->establishCSRFTokenState();

        // Build request params
        $requestParams = array(
            'response_type' => 'code',
            'client_id' => $this->appId,
            'state' => $this->getState(),
        );

        // Look for the redirect URL
        if (isset($options['redirect_uri'])) {
            $requestParams['redirect_uri'] = $options['redirect_uri'];
        } else {
            $requestParams['redirect_uri'] = $urlGenerator->getCurrentUrl();
        }

        // Save the redirect url for later
        $this->getStorage()->set('redirect_url', $requestParams['redirect_uri']);

        // if 'scope' is passed as an array, convert to space separated list
        $scopeParams = isset($options['scope']) ? $options['scope'] : null;
        if ($scopeParams) {
            //if scope is an array
            if (is_array($scopeParams)) {
                $requestParams['scope'] = implode(' ', $scopeParams);
            } elseif (is_string($scopeParams)) {
                //if scope is a string with ',' => make it to an array
                $requestParams['scope'] = str_replace(',', ' ', $scopeParams);
            }
        }

        return $urlGenerator->getUrl(
            'www',
            'uas/oauth2/authorization',
            $requestParams
        );
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

        if (GlobalVariableGetter::has('code')) {
            if ($storage->get('code') === GlobalVariableGetter::get('code')) {
                //we have already validated this code
                return;
            }

            //if stored state does not exists
            if (null === $state = $this->getState()) {
                throw new LinkedInApiException('Could not find a stored CSRF state token.');
            }

            //if state exists in the request
            if (!GlobalVariableGetter::has('state')) {
                throw new LinkedInApiException('Could not find a CSRF state token in the request.');
            }

            //if state exists in session and in request and if they are not equal
            if ($state !== GlobalVariableGetter::get('state')) {
                throw new LinkedInApiException('The CSRF state token from the request does not match the stored token.');
            }

            // CSRF state has done its job, so clear it
            $this->setState(null);
            $storage->clear('state');

            return GlobalVariableGetter::get('code');
        }

        return;
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
     * Clear the storage.
     *
     * @return $this
     */
    public function clearStorage()
    {
        $this->getStorage()->clearAll();

        return $this;
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
     * @param DataStorageInterface $storage
     *
     * @return $this
     */
    public function setStorage(DataStorageInterface $storage)
    {
        $this->storage = $storage;

        return $this;
    }
}
