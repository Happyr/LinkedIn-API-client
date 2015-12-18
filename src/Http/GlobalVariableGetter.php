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
     * @param $name
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
     * @param $name
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
