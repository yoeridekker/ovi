<?php 

namespace Ovi\Helpers;

class Helper
{
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;

        $keys = explode('.', $key);

        while (count($keys) > 1)
            {
                $key = array_shift($keys);

                if ( ! isset($array[$key]) || ! is_array($array[$key]))
                {
                    $array[$key] = array();
                }

            $array =& $array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;
        
        if (isset($array[$key])) return $array[$key];
        
        foreach (explode('.', $key) as $segment)
        {
            if ( ! is_array($array) || ! array_key_exists($segment, $array))
            {
                return value($default);
            }
            $array = $array[$segment];
        }
        
        return $array;
    }

}
