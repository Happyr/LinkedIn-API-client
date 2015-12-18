<?php

namespace Happyr\LinkedIn\Storage;

/**
 * @author Tobias Nyholm
 */
abstract class BaseDataStorage implements DataStorageInterface
{
    public static $validKeys = array('state', 'code', 'access_token', 'user', 'redirect_url');

    /**
     * {@inheritdoc}
     */
    public function clearAll()
    {
        foreach (self::$validKeys as $key) {
            $this->clear($key);
        }
    }

    /**
     * Generate a session name.
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
