<?php

namespace Happyr\LinkedIn;

use Happyr\LinkedIn\Exception\LoginError;
use Happyr\LinkedIn\Http\UrlGeneratorInterface;
use Happyr\LinkedIn\Storage\DataStorageInterface;
use Http\Client\HttpClient;
use Http\Message\MessageFactory;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface that lets you talk to LinkedIn api.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface LinkedInInterface
{
    /**
     * Is the current user authenticated?
     *
     * @return bool
     */
    public function isAuthenticated();

    /**
     * Make an API call. Read about what calls that are possible here: https://developer.linkedin.com/docs/rest-api.
     *
     * Example:
     * $linkedIn->api('GET', '/v1/people/~:(id,firstName,lastName,headline)');
     *
     * The options:
     * - body: the body of the request
     * - format: the format you are using to send the request
     * - headers: array with headers to use
     * - response_data_type: the data type to get back
     * - query: query parameters to the request
     *
     * @param string $method   This is the HTTP verb
     * @param string $resource everything after the domain in the URL.
     * @param array  $options  See the readme for option description.
     *
     * @return mixed this depends on the response_data_type parameter.
     */
    public function api($method, $resource, array $options = []);

    /**
     * Get a login URL where the user can put his/hers LinkedIn credentials and authorize the application.
     *
     * The options:
     * - redirect_uri: the url to go to after a successful login
     * - scope: comma (or space) separated list of requested extended permissions
     *
     * @param array $options Provide custom parameters
     *
     * @return string The URL for the login flow
     */
    public function getLoginUrl($options = []);

    /**
     * See docs for LinkedIn::api().
     *
     * @param string $resource
     * @param array  $options
     *
     * @return mixed
     */
    public function get($resource, array $options = []);

    /**
     * See docs for LinkedIn::api().
     *
     * @param string $resource
     * @param array  $options
     *
     * @return mixed
     */
    public function post($resource, array $options = []);

    /**
     * Clear the data storage. This will forget everything about the user and authentication process.
     *
     * @return $this
     */
    public function clearStorage();

    /**
     * If the user has canceled the login we will return with an error.
     *
     * @return bool
     */
    public function hasError();

    /**
     * Returns a LoginError or null.
     *
     * @return LoginError|null
     */
    public function getError();

    /**
     * Set the default format to use when sending requests.
     *
     * @param string $format
     *
     * @return $this
     */
    public function setFormat($format);

    /**
     * Set the default data type to be returned as a response.
     *
     * @param string $responseDataType
     *
     * @return $this
     */
    public function setResponseDataType($responseDataType);

    /**
     * Get the last response. This will always return a PSR-7 response no matter of the data type used.
     *
     * @return ResponseInterface|null
     */
    public function getLastResponse();

    /**
     * Returns an access token. If we do not have one in memory, try to fetch one from a *code* in the $_REQUEST.
     *
     * @return AccessToken|null The access token of null if the access token is not found
     */
    public function getAccessToken();

    /**
     * If you have stored a previous access token in a storage (database) you could set it here. After setting an
     * access token you have to make sure to verify it is still valid by running LinkedIn::isAuthenticated.
     *
     * @param string|AccessToken $accessToken
     *
     * @return $this
     */
    public function setAccessToken($accessToken);

    /**
     * Set a URL generator.
     *
     * @param UrlGeneratorInterface $urlGenerator
     *
     * @return $this
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator);

    /**
     * Set a data storage.
     *
     * @param DataStorageInterface $storage
     *
     * @return $this
     */
    public function setStorage(DataStorageInterface $storage);

    /**
     * Set a http client.
     *
     * @param HttpClient $client
     *
     * @return $this
     */
    public function setHttpClient(HttpClient $client);

    /**
     * Set a http message factory.
     *
     * @param MessageFactory $factory
     *
     * @return $this
     */
    public function setHttpMessageFactory(MessageFactory $factory);
}
