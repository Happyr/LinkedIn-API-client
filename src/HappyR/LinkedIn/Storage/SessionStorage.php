<?php


namespace HappyR\LinkedIn\Storage;

use HappyR\LinkedIn\Exceptions\LinkedInApiException;

/**
 * Class SessionStorage
 *
 * @author Tobias Nyholm
 *
 */
class SessionStorage extends DataStorage
{
    /**
     * {@inheritDoc}
     */
    public function set($key, $value) {
        if (!in_array($key, self::$validKeys)) {
            throw new LinkedInApiException(sprintf('Unsupported key ("%s") passed to set.', $key));
        }

        $session_var_name = $this->constructSessionVariableName($key);
        $_SESSION[$session_var_name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = false) {
        if (!in_array($key, self::$validKeys)) {
            return $default;
        }

        $session_var_name = $this->constructSessionVariableName($key);
        return isset($_SESSION[$session_var_name]) ? $_SESSION[$session_var_name] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function clear($key) {
        if (!in_array($key, self::$validKeys)) {
            throw new LinkedInApiException(sprintf('Unsupported key ("%s") passed to clear.', $key));
        }

        $session_var_name = $this->constructSessionVariableName($key);
        if (isset($_SESSION[$session_var_name])) {
            unset($_SESSION[$session_var_name]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clearAll() {
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
    protected function constructSessionVariableName($key) {
        return 'linkedIn_'.$key;
    }
} 