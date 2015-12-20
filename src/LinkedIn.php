<?php

namespace Happyr\LinkedIn;

use Happyr\LinkedIn\Exception\LoginError;
use Happyr\LinkedIn\Http\GlobalVariableGetter;
use Happyr\LinkedIn\Http\RequestManager;
use Happyr\LinkedIn\Http\ResponseConverter;
use Happyr\LinkedIn\Http\UrlGenerator;
use Happyr\LinkedIn\Http\UrlGeneratorInterface;
use Happyr\LinkedIn\Storage\DataStorageInterface;
use Http\Client\HttpClient;
use Psr\Http\Message\ResponseInterface;

/**
 * Class LinkedIn lets you talk to LinkedIn api.
 *
 * When a new user arrives and want to authenticate here is whats happens:
 * 1. You redirect him to whatever url getLoginUrl() returns.
 * 2. The user logs in on www.linkedin.com and authorize your application.
 * 3. The user returns to your site with a *code* in the the $_REQUEST.
 * 4. You call isAuthenticated() or getAccessToken()
 * 5. We don't got an access token (only a *code*). So getAccessToken() calls fetchNewAccessToken()
 * 6. fetchNewAccessToken() gets the *code* and calls getAccessTokenFromCode()
 * 7. getAccessTokenFromCode() makes a request to www.linkedin.com and exchanges the *code* for an access token
 * 8. With a valid access token we can query the api for the user
 * 9. When you make a second request to the api you skip the authorization (1-3) and
 *     the "*code* for access token exchange" (5-7).
 *
 * @author Tobias Nyholm
 */
class LinkedIn
{
    /**
     * The OAuth access token received in exchange for a valid authorization
     * code.  null means the access token has yet to be determined.
     *
     * @var AccessToken
     */
    protected $accessToken = null;

    /**
     * @var string format
     */
    private $format;

    /**
     * @var string responseFormat
     */
    private $responseDataType;

    /**
     * @var ResponseInterface
     */
    private $lastResponse;

    /**
     * @var RequestManager
     */
    private $requestManager;

    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * Constructor.
     *
     * @param string $appId
     * @param string $appSecret
     * @param string $format           'json', 'xml'
     * @param string $responseDataType 'array', 'string', 'simple_xml' 'psr7', 'stream'
     */
    public function __construct($appId, $appSecret, $format = 'json', $responseDataType = 'array')
    {
        $this->format = $format;
        $this->responseDataType = $responseDataType;

        $this->requestManager = new RequestManager();
        $this->authenticator = new Authenticator($this->requestManager, $appId, $appSecret);
    }

    /**
     * Is the current user authenticated?
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return false;
        }

        $user = $this->api('GET', '/v1/people/~:(id,firstName,lastName)', array('responseDataType' => 'array'));

        return !empty($user['id']);
    }

    /**
     * Make an API call.
     *
     * $linkedIn->api('GET', '/v1/people/~:(id,firstName,lastName,headline)');
     *
     * @param string $method   This is the HTTP verb
     * @param string $resource everything after the domain in the URL.
     * @param array  $options  [optional] This is the options you may pass to the request. You might be interested
     *                         in setting values for 'query', 'headers', 'body' or 'response_data_type'. See the readme for more.
     *
     * @return array|\SimpleXMLElement|string What the function return depends on the responseDataType parameter.
     */
    public function api($method, $resource, array $options = array())
    {
        // Add access token to the headers
        $options['headers']['Authorization'] = sprintf('Bearer %s', (string) $this->getAccessToken());

        // Do logic and adjustments to the options
        $requestFormat = $this->filterRequestOption($options);

        // Generate an url
        $url = $this->getUrlGenerator()->getUrl(
            'api',
            $resource,
            isset($options['query']) ? $options['query'] : array()
        );

        //Get the response data format
        if (isset($options['response_data_type'])) {
            $responseDataType = $options['response_data_type'];
        } else {
            $responseDataType = $this->getResponseDataType();
        }

        $body = isset($options['body']) ? $options['body'] : null;
        $this->lastResponse = $this->getRequestManager()->sendRequest($method, $url, $options['headers'], $body);

        return ResponseConverter::convert($this->lastResponse, $requestFormat, $responseDataType);
    }

    /**
     * Modify and filter the request options. Make sure we use the correct query parameters and headers.
     *
     * @param array &$options
     *
     * @return string
     */
    protected function filterRequestOption(array &$options)
    {
        if (isset($options['json'])) {
            $options['format'] = 'json';
            $options['body'] = json_encode($options['json']);
        } elseif (!isset($options['format'])) {
            $options['format'] = $this->getFormat();
        }

        // Set correct headers for this format
        switch ($options['format']) {
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
        $format = $options['format'];
        unset($options['format']);

        return $format;
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
     * @param array $options Provide custom parameters
     *
     * @return string The URL for the login flow
     */
    public function getLoginUrl($options = array())
    {
        return $this->getAuthenticator()->getLoginUrl($this->getUrlGenerator(), $options);
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
     * Clear the storage. This will forget everything about the user and authentication process.
     */
    public function clearStorage()
    {
        $this->getAuthenticator()->clearStorage();
    }

    /**
     * If the user has canceled the login we will return with an error.
     *
     * @return bool
     */
    public function hasError()
    {
        return GlobalVariableGetter::has('error');
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

        return new LoginError(GlobalVariableGetter::get('error'), GlobalVariableGetter::get('error_description'));
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
     * @return string
     */
    public function getResponseDataType()
    {
        return $this->responseDataType;
    }

    /**
     * @param string $responseDataType
     *
     * @return LinkedIn
     */
    public function setResponseDataType($responseDataType)
    {
        $this->responseDataType = $responseDataType;

        return $this;
    }

    /**
     * Get headers from last response.
     *
     * @return array
     */
    public function getLastHeaders()
    {
        if ($this->lastResponse === null) {
            return [];
        }

        return $this->lastResponse->getHeaders();
    }

    /**
     * Determines the access token that should be used for API calls.
     *
     * @return AccessToken|null The access token of null if the access token is not found
     */
    public function getAccessToken()
    {
        if ($this->accessToken === null) {
            $newAccessToken = $this->getAuthenticator()->fetchNewAccessToken($this->getUrlGenerator());
            if ($newAccessToken !== null) {
                $this->setAccessToken($newAccessToken);
            }
        }

        // return the new access token or null if none found
        return $this->accessToken;
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
        $this->getAuthenticator()->setStorage($storage);

        return $this;
    }

    /**
     * @param HttpClient $client
     *
     * @return $this
     *
     * @deprecated This function will be removed in 0.7.0. It will be replaced by setHttpAdapter
     */
    public function setHttpClient(HttpClient $client)
    {
        $this->getRequestManager()->setHttpClient($client);

        return $this;
    }

    /**
     * @return RequestManager
     */
    protected function getRequestManager()
    {
        return $this->requestManager;
    }

    /**
     * @return Authenticator
     */
    protected function getAuthenticator()
    {
        return $this->authenticator;
    }
}
