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
use Http\Message\MessageFactory;
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
 * 6. fetchNewAccessToken() gets the *code* from the $_REQUEST and calls getAccessTokenFromCode()
 * 7. getAccessTokenFromCode() makes a request to www.linkedin.com and exchanges the *code* for an access token
 * 8. When you have the access token you should store it in a database and/or query the API.
 * 9. When you make a second request to the API we have the access token in memory, so we don't go through all these
 *    authentication steps again.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LinkedIn implements LinkedInInterface
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
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        $accessToken = $this->getAccessToken();
        if ($accessToken === null) {
            return false;
        }

        $user = $this->api('GET', '/v1/people/~:(id,firstName,lastName)', ['format' => 'json', 'response_data_type' => 'array']);

        return !empty($user['id']);
    }

    /**
     * {@inheritdoc}
     */
    public function api($method, $resource, array $options = [])
    {
        // Add access token to the headers
        $options['headers']['Authorization'] = sprintf('Bearer %s', (string) $this->getAccessToken());

        // Do logic and adjustments to the options
        $requestFormat = $this->filterRequestOption($options);

        // Generate an url
        $url = $this->getUrlGenerator()->getUrl(
            'api',
            $resource,
            isset($options['query']) ? $options['query'] : []
        );

        $body = isset($options['body']) ? $options['body'] : null;
        $this->lastResponse = $this->getRequestManager()->sendRequest($method, $url, $options['headers'], $body);

        //Get the response data format
        if (isset($options['response_data_type'])) {
            $responseDataType = $options['response_data_type'];
        } else {
            $responseDataType = $this->getResponseDataType();
        }

        return ResponseConverter::convert($this->lastResponse, $requestFormat, $responseDataType);
    }

    /**
     * Modify and filter the request options. Make sure we use the correct query parameters and headers.
     *
     * @param array &$options
     *
     * @return string the request format to use
     */
    protected function filterRequestOption(array &$options)
    {
        if (isset($options['json'])) {
            $options['format'] = 'json';
            $options['body'] = json_encode($options['json']);
        } elseif (!isset($options['format'])) {
            // Make sure we always have a format
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

        return $options['format'];
    }

    /**
     * {@inheritdoc}
     */
    public function getLoginUrl($options = [])
    {
        $urlGenerator = $this->getUrlGenerator();

        // Set redirect_uri to current URL if not defined
        if (!isset($options['redirect_uri'])) {
            $options['redirect_uri'] = $urlGenerator->getCurrentUrl();
        }

        return $this->getAuthenticator()->getLoginUrl($urlGenerator, $options);
    }

    /**
     * See docs for LinkedIn::api().
     *
     * @param string $resource
     * @param array  $options
     *
     * @return mixed
     */
    public function get($resource, array $options = [])
    {
        return $this->api('GET', $resource, $options);
    }

    /**
     * See docs for LinkedIn::api().
     *
     * @param string $resource
     * @param array  $options
     *
     * @return mixed
     */
    public function post($resource, array $options = [])
    {
        return $this->api('POST', $resource, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function clearStorage()
    {
        $this->getAuthenticator()->clearStorage();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasError()
    {
        return GlobalVariableGetter::has('error');
    }

    /**
     * {@inheritdoc}
     */
    public function getError()
    {
        if ($this->hasError()) {
            return new LoginError(GlobalVariableGetter::get('error'), GlobalVariableGetter::get('error_description'));
        }
    }

    /**
     * Get the default format to use when sending requests.
     *
     * @return string
     */
    protected function getFormat()
    {
        return $this->format;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Get the default data type to be returned as a response.
     *
     * @return string
     */
    protected function getResponseDataType()
    {
        return $this->responseDataType;
    }

    /**
     * {@inheritdoc}
     */
    public function setResponseDataType($responseDataType)
    {
        $this->responseDataType = $responseDataType;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessToken()
    {
        if ($this->accessToken === null) {
            if (null !== $newAccessToken = $this->getAuthenticator()->fetchNewAccessToken($this->getUrlGenerator())) {
                $this->setAccessToken($newAccessToken);
            }
        }

        // return the new access token or null if none found
        return $this->accessToken;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function setStorage(DataStorageInterface $storage)
    {
        $this->getAuthenticator()->setStorage($storage);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setHttpClient(HttpClient $client)
    {
        $this->getRequestManager()->setHttpClient($client);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setHttpMessageFactory(MessageFactory $factory)
    {
        $this->getRequestManager()->setMessageFactory($factory);

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
