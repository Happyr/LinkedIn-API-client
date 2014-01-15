<?php


namespace HappyR\LinkedIn\Storage;


/**
 * Class DataStorageInterface
 *
 * @author Tobias Nyholm
 *
 */
abstract class DataStorage
{
    public static $validKeys = array('state', 'code', 'access_token', 'user_id');

    /**
     * Stores the given ($key, $value) pair, so that future calls to
     * getPersistentData($key) return $value. This call may be in another request.
     *
     * @param string $key
     * @param array $value
     *
     * @return void
     */
    abstract public function set($key, $value);

    /**
     * Get the data for $key, persisted by BaseFacebook::setPersistentData()
     *
     * @param string $key The key of the data to retrieve
     * @param boolean $default The default value to return if $key is not found
     *
     * @return mixed
     */
    abstract public function get($key, $default = false);

    /**
     * Clear the data with $key from the persistent storage
     *
     * @param string $key
     * @return void
     */
    abstract public function clear($key);

    /**
     * Clear all data from the persistent storage
     *
     * @return void
     */
    abstract public function clearAll();
}