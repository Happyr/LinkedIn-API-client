<?php

namespace Happyr\LinkedIn\Http;

/**
 * Look in $_REQUEST and $_GET if there is a variable we want.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class GlobalVariableGetter
{
    /**
     * Returns true iff the $_REQUEST or $_GET variables has a key with $name.
     *
     * @param string $name
     *
     * @return bool
     */
    public static function has($name)
    {
        if (isset($_REQUEST[$name])) {
            return true;
        }

        return isset($_GET[$name]);
    }

    /**
     * Returns the value in $_REQUEST[$name] or $_GET[$name] if the former was empty. If no value found, return null.
     *
     * @param string $name
     *
     * @return mixed|null
     */
    public static function get($name)
    {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }

        if (isset($_GET[$name])) {
            return $_GET[$name];
        }

        return;
    }
}
