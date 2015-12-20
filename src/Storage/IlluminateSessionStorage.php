<?php

namespace Happyr\LinkedIn\Storage;

use Illuminate\Support\Facades\Session;

/**
 * Store data in a IlluminateSession.
 *
 * @author Andreas Creten
 */
class IlluminateSessionStorage extends BaseDataStorage
{
    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->validateKey($key);
        $name = $this->getStorageKeyId($key);

        return Session::put($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $this->validateKey($key);
        $name = $this->getStorageKeyId($key);

        return Session::get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($key)
    {
        $this->validateKey($key);
        $name = $this->getStorageKeyId($key);

        return Session::forget($name);
    }
}
