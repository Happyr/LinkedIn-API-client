<?php

namespace HappyR\LinkedIn\Http;

/**
 * Class UrlGeneratorInterface
 *
 * Time to time we need build a URL or get the current URL
 *
 * @author Tobias Nyholm
 *
 */
interface UrlGeneratorInterface
{
    /**
     * Build the URL for given domain alias, path and parameters.
     *
     * @param $name string The name of the domain
     * @param $path string Optional path (without a leading slash)
     * @param $params array Optional query parameters
     *
     * @return string The URL for the given parameters
     */
    public function getUrl($name, $path = '', $params = array());

    /**
     * Returns the Current URL,
     * not persist.
     *
     * @return string The current URL
     */
    public function getCurrentUrl();

    /**
     * Should we trust forwarded headers?
     *
     * @param boolean $trustForwarded
     *
     * @return $this
     */
    public function setTrustForwarded($trustForwarded);
}