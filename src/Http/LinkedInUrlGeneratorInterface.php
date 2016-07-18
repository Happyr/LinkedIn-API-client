<?php

namespace Happyr\LinkedIn\Http;

/**
 * An interface to generate LinkedIn specific urls.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface LinkedInUrlGeneratorInterface
{
    /**
     * Build the URL for given domain alias, path and parameters.
     *
     * @param $name string The name of the domain, 'www' or 'api'
     * @param $path string without a leading slash
     * @param $params array query parameters
     *
     * @return string The URL for the given parameters. The URL query  MUST be build with PHP_QUERY_RFC3986
     */
    public function getUrl($name, $path = '', $params = []);
}
