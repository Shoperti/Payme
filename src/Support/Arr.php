<?php

namespace Shoperti\PayMe\Support;

use InvalidArgumentException;

/**
 * This is the arr class.
 *
 * @author Joseph Cohen <joseph.cohen@dinkbit.com>
 */
class Arr
{
    /**
     * Get an item from an array.
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get(&$array, $key, $default = null)
    {
        return isset($array[$key]) ? $array[$key] : $default;
    }

    /**
     * Check the array contains the required keys.
     *
     * @param string[] $options
     * @param string[] $required
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public static function requires(array $options, array $required = [])
    {
        foreach ($required as $key) {
            if (!array_key_exists(trim($key), $options)) {
                throw new InvalidArgumentException("Missing required parameter: {$key}");
            }
        }
    }

    /**
     * Filter null items from an array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function filters(array $array)
    { 
        return array_filter($array, function ($item) {
            return $item !== null;
        });
    }
}
