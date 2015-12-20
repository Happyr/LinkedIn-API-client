<?php

namespace Happyr\LinkedIn\Storage;

use Happyr\LinkedIn\Exception\InvalidArgumentException;

/**
 * Store data in the global session.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class SessionStorage extends BaseDataStorage
{
    public function __construct()
    {
        //start the session if it not already been started
        if (php_sapi_name() !== 'cli') {
            if (session_id() === '') {
                session_start();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        if (!in_array($key, self::$validKeys)) {
            throw new InvalidArgumentException('Unsupported key "%s" passed to set.', $key);
        }

        $name = $this->constructSessionVariableName($key);
        $_SESSION[$name] = $value;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function clear($key)
    {
        if (!in_array($key, self::$validKeys)) {
            throw new InvalidArgumentException('Unsupported key "%s" passed to clear.', $key);
        }

        $name = $this->constructSessionVariableName($key);
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }
}
