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

    protected $ignoreNonActiveSession = false;

    /**
     * @param bool $ignoreNonActiveSession
     */
    public function __construct($ignoreNonActiveSession = false)
    {
        $this->ignoreNonActiveSession = $ignoreNonActiveSession;
    }

    /**
     * @param bool $ignore
     */
    public function ignoreNonActiveSession($ignore = true)
    {
        $this->ignoreNonActiveSession = $ignore;
    }

    /**
     * {@inheritDoc}
     */
    public function set($key, $value)
    {
        if (!$this->ignoreNonActiveSession && session_status() == PHP_SESSION_NONE) {
            throw new LinkedInApiException("There is no active session to be used for storage.");
        }

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
        if (!$this->ignoreNonActiveSession && session_status() == PHP_SESSION_NONE) {
            throw new LinkedInApiException("There is no active session to be used for storage.");
        }

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
        if (!$this->ignoreNonActiveSession && session_status() == PHP_SESSION_NONE) {
            throw new LinkedInApiException("There is no active session to be used for storage.");
        }

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
