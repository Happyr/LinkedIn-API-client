<?php

namespace Happyr\LinkedIn\Storage;

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
        $this->validateKey($key);

        $name = $this->getStorageKeyId($key);
        $_SESSION[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->validateKey($key);
        $name = $this->getStorageKeyId($key);

        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key)
    {
        $this->validateKey($key);

        $name = $this->getStorageKeyId($key);
        if (isset($_SESSION[$name])) {
            unset($_SESSION[$name]);
        }
    }
}
