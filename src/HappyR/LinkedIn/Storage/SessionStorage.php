<?php

namespace HappyR\LinkedIn\Storage;

use HappyR\LinkedIn\Exceptions\LinkedInApiException;

/**
 * Class SessionStorage
 *
 * Store data in the session.
 *
 * @author Tobias Nyholm
 *
 */
class SessionStorage implements DataStorageInterface
{
    public static $validKeys = array('state', 'code', 'access_token', 'user');

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        if (!in_array($key, self::$validKeys)) {
            throw new LinkedInApiException(sprintf('Unsupported key ("%s") passed to set.', $key));
        }

        $name = $this->constructSessionVariableName($key);
        $_SESSION[$name] = $value;
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
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $default;
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
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clearAll()
    {
        foreach (self::$validKeys as $key) {
            $this->clear($key);
        }
    }

    /**
     * Generate a session name
     *
     * @param $key
     *
     * @return string
     */
    protected function constructSessionVariableName($key)
    {
        return 'linkedIn_'.$key;
    }
}