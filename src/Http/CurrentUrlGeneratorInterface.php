<?php

namespace Happyr\LinkedIn\Http;

/**
 * An interface to get the current URL.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
interface CurrentUrlGeneratorInterface
{
    /**
     * Returns the current URL.
     *
     * @return string The current URL
     */
    public function getCurrentUrl();

    /**
     * Should we trust forwarded headers?
     *
     * @param bool $trustForwarded
     *
     * @return $this
     */
    public function setTrustForwarded($trustForwarded);
}
