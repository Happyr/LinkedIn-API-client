<?php

namespace Happyr\LinkedIn;

use Happyr\LinkedIn\Exception\LinkedInException;
use Happyr\LinkedIn\Http\LinkedInUrlGeneratorInterface;
use Happyr\LinkedIn\Storage\DataStorageInterface;

/**
 * This interface is responsible for the authentication process with LinkedIn.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface AuthenticatorInterface
{
    /**
     * Tries to get a new access token from data storage or code. If it fails, it will return null.
     *
     * @param LinkedInUrlGeneratorInterface $urlGenerator
     *
     * @return AccessToken|null A valid user access token, or null if one could not be fetched.
     *
     * @throws LinkedInException
     */
    public function fetchNewAccessToken(LinkedInUrlGeneratorInterface $urlGenerator);

    /**
     * Generate a login url.
     *
     * @param LinkedInUrlGeneratorInterface $urlGenerator
     * @param array                         $options
     *
     * @return string
     */
    public function getLoginUrl(LinkedInUrlGeneratorInterface $urlGenerator, $options = []);

    /**
     * Clear the storage.
     *
     * @return $this
     */
    public function clearStorage();

    /**
     * @param DataStorageInterface $storage
     *
     * @return $this
     */
    public function setStorage(DataStorageInterface $storage);
}
