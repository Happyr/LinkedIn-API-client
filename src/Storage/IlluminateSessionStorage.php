<?php

namespace Happyr\LinkedIn\Storage;

use Happyr\LinkedIn\Exception\InvalidArgumentException;
use Happyr\LinkedIn\Exception\LinkedInTransferException;
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
        if (!in_array($key, self::$validKeys)) {
            throw new InvalidArgumentException('Unsupported key "%s" passed to set.', $key);
        }

        $name = $this->constructSessionVariableName($key);

        return Session::put($name, $value);
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

        return Session::get($name);
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

        return Session::forget($name);
    }
}
