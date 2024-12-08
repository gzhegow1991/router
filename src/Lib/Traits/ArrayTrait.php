<?php

namespace Gzhegow\Router\Lib\Traits;

trait ArrayTrait
{
    /**
     * > gzhegow, разбивает массив на два, где в первом все цифровые ключи (список), во втором - все буквенные (словарь)
     *
     * @return array{
     *     0: array<int, mixed>,
     *     1: array<string, mixed>
     * }
     */
    public static function array_kwargs(array $src = null) : array
    {
        if (! isset($src)) return [];

        $list = [];
        $dict = [];

        foreach ( $src as $idx => $val ) {
            is_int($idx)
                ? ($list[ $idx ] = $val)
                : ($dict[ $idx ] = $val);
        }

        return [ $list, $dict ];
    }


    /**
     * > gzhegow, строит индекс ключей (int)
     * > [ 0 => 1, 2 => true, 3 => false ] -> [ 1 => true, 2 => true, 3 => false ]
     *
     * @return array<int, bool>
     */
    public static function array_index_int(array $array, array ...$arrays) : array
    {
        array_unshift($arrays, $array);

        $index = array_merge(...$arrays);

        $result = [];

        foreach ( $index as $k => $v ) {
            if (is_int($v)) {
                $key = $v;

                $result[ $key ] = true;

            } elseif (! isset($result[ $k ])) {
                $key = $k;

                $v = (bool) $v;

                if ($v) {
                    $result[ $key ] = true;
                }
            }
        }

        return $result;
    }

    /**
     * > gzhegow, строит индекс ключей (string)
     * > [ 0 => 'key1', 'key2' => true, 'key3' => false ] -> [ 'key1' => true, 'key2' => true, 'key3' => false ]
     *
     * @return array<string, bool>
     */
    public static function array_index_string(array $array, array ...$arrays) : array
    {
        array_unshift($arrays, $array);

        $index = array_merge(...$arrays);

        $result = [];

        foreach ( $index as $k => $v ) {
            if (is_string($k) && ($k !== '')) {
                $key = $k;

                $v = (bool) $v;

                if ($v) {
                    $result[ $key ] = true;
                }

            } elseif (is_string($v) && ! isset($result[ $v ])) {
                $key = $v;

                $result[ $key ] = true;
            }
        }

        return $result;
    }


    /**
     * > gzhegow, встроенная функция всегда требует два массива на вход, вынуждая разруливать ифами то, что не нужно
     */
    public static function array_diff(array ...$arrays) : array
    {
        if (! $arrays) {
            return [];
        }

        if (count($arrays) === 1) {
            return $arrays[ 0 ];
        }

        $result = array_diff(...$arrays);

        return $result;
    }

    /**
     * > gzhegow, встроенная функция всегда требует два массива на вход, вынуждая разруливать ифами то, что не нужно
     */
    public static function array_diff_key(array ...$arrays) : array
    {
        if (! $arrays) {
            return [];
        }

        if (count($arrays) === 1) {
            return $arrays[ 0 ];
        }

        $result = array_diff_key(...$arrays);

        return $result;
    }

    /**
     * > gzhegow, встроенная функция всегда требует два массива на вход, вынуждая разруливать ифами то, что не нужно
     */
    public static function array_intersect(array ...$arrays) : array
    {
        if (! $arrays) {
            return [];
        }

        if (count($arrays) === 1) {
            return $arrays[ 0 ];
        }

        $result = array_intersect(...$arrays);

        return $result;
    }

    /**
     * > gzhegow, встроенная функция всегда требует два массива на вход, вынуждая разруливать ифами то, что не нужно
     */
    public static function array_intersect_key(array ...$arrays) : array
    {
        if (! $arrays) {
            return [];
        }

        if (count($arrays) === 1) {
            return $arrays[ 0 ];
        }

        $result = array_intersect_key(...$arrays);

        return $result;
    }
}
