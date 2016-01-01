<?php

namespace Happyr\LinkedIn\Storage;

/**
 * We need to store data somewhere. It might be in a apc cache, filesystem cache, database or in the session.
 * We need it to protect us from CSRF attacks and to reduce the requests to the API.
 *
 * @author Tobias Nyholm
 */
interface DataStorageInterface
{
    /**
     * Stores the given ($key, $value) pair, so that future calls to
     * getPersistentData($key) return $value. This call may be in another request.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value);

    /**
     * Get the data for $key, persisted by BaseFacebook::setPersistentData().
     *
     * @param string $key The key of the data to retrieve
     *
     * @return mixed
     */
    public function get($key);

    /**
     * Clear the data with $key from the persistent storage.
     *
     * @param string $key
     */
    public function clear($key);

    /**
     * Clear all data from the persistent storage.
     */
    public function clearAll();
}
