<?php

namespace HappyR\LinkedIn\Storage;

/**
 * Class DataStorage
 *
 * We need to store data some where. It might be in a apc cache, filesystem cache, database or in the session.
 * We need it to protect us from CSRF attacks and to reduce the requests to the API.
 *
 * @author Tobias Nyholm
 *
 */
interface DataStorageInterface
{
    /**
     * Stores the given ($key, $value) pair, so that future calls to
     * getPersistentData($key) return $value. This call may be in another request.
     *
     * @param string $key
     * @param array $value
     *
     * @return void
     */
    public function set($key, $value);

    /**
     * Get the data for $key, persisted by BaseFacebook::setPersistentData()
     *
     * @param string $key The key of the data to retrieve
     * @param boolean $default The default value to return if $key is not found
     *
     * @return mixed
     */
    public function get($key, $default = false);

    /**
     * Clear the data with $key from the persistent storage
     *
     * @param string $key
     * @return void
     */
    public function clear($key);

    /**
     * Clear all data from the persistent storage
     *
     * @return void
     */
    public function clearAll();
}