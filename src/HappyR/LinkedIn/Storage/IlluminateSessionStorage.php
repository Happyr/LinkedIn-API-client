<?php

namespace HappyR\LinkedIn\Storage;

use HappyR\LinkedIn\Exceptions\LinkedInApiException;
use Illuminate\Support\Facades\Session;

/**
 * Class SessionStorage
 *
 * Store data in the session.
 *
 * @author Andreas Creten
 *
 */
class IlluminateSessionStorage extends SessionStorage
{
    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        if (!in_array($key, self::$validKeys)) {
            throw new LinkedInApiException(sprintf('Unsupported key ("%s") passed to set.', $key));
        }

        $name = $this->constructSessionVariableName($key);
        return Session::put($name, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = false)
    {
        if (!in_array($key, self::$validKeys)) {
            return $default;
        }

        $name = $this->constructSessionVariableName($key);
        return Session::get($name);
    }

    /**
     * {@inheritDoc}
     */
    public function clear($key)
    {
        if (!in_array($key, self::$validKeys)) {
            throw new LinkedInApiException(sprintf('Unsupported key ("%s") passed to clear.', $key));
        }

        $name = $this->constructSessionVariableName($key);
        return Session::forget($name);
    }
}