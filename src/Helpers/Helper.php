<?php

namespace Ovi\Helpers;

/**
 * Class Helper
 *
 * Array helper utilities supporting dot-notation access.
 */
class Helper
{
    /**
     * Set a value within an array using dot-notation keys.
     *
     * @param array $array Reference to the array to modify.
     * @param string|null $key Dot-notation key (or null to replace the whole array).
     * @param mixed $value Value to set.
     * @return array The modified array.
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }

            $array =& $array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Get a value from an array using a dot-notation key.
     *
     * @param array $array The source array.
     * @param string|null $key Dot-notation key (or null to return the entire array).
     * @param mixed $default Default value when path is not found.
     * @return mixed The value found or default.
     */
    public static function get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;

        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return is_callable($default) ? $default() : $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Pluck a single field from an array of arrays/objects.
     *
     * @param array $array List of arrays or objects.
     * @param string $key Field/property name to pluck.
     * @return array List of plucked values.
     */
    function pluck($array, $key)
    {
        return array_map(function ($v) use ($key) {
            return is_object($v) ? $v->$key : $v[$key];
        }, $array);
    }
}
