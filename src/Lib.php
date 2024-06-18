<?php

namespace Gzhegow\Router;

class Lib
{
    /**
     * > gzhegow, выводит короткую и наглядную форму содержимого переменной в виде строки
     */
    public static function php_dump($value, int $maxlen = null) : string
    {
        if (is_string($value)) {
            $_value = ''
                . '{ '
                . 'string(' . strlen($value) . ')'
                . ' "'
                . ($maxlen
                    ? (substr($value, 0, $maxlen) . '...')
                    : $value
                )
                . '"'
                . ' }';

        } elseif (! is_iterable($value)) {
            $_value = null
                ?? (($value === null) ? '{ NULL }' : null)
                ?? (($value === false) ? '{ FALSE }' : null)
                ?? (($value === true) ? '{ TRUE }' : null)
                ?? (is_object($value) ? ('{ object(' . get_class($value) . ' # ' . spl_object_id($value) . ') }') : null)
                ?? (is_resource($value) ? ('{ resource(' . gettype($value) . ' # ' . ((int) $value) . ') }') : null)
                //
                ?? (is_int($value) ? (var_export($value, 1)) : null) // INF
                ?? (is_float($value) ? (var_export($value, 1)) : null) // NAN
                //
                ?? null;

        } else {
            $_value = [];
            foreach ( $value as $k => $v ) {
                $_value[ $k ] = null
                    ?? (is_array($v) ? '{ array(' . count($v) . ') }' : null)
                    // ! recursion
                    ?? static::php_dump($v, $maxlen);
            }

            ob_start();
            var_dump($_value);
            $_value = ob_get_clean();

            if (is_object($value)) {
                $_value = '{ iterable(' . get_class($value) . ' # ' . spl_object_id($value) . '): ' . $_value . ' }';
            }

            $_value = trim($_value);
            $_value = preg_replace('/\s+/', ' ', $_value);
        }

        if (null === $_value) {
            throw static::php_throwable(
                'Unable to dump variable'
            );
        }

        return $_value;
    }

    /**
     * > gzhegow, перебрасывает исключение на "тихое", если из библиотеки внутреннее постоянно подсвечивается в PHPStorm
     *
     * @return \LogicException|\RuntimeException|null
     */
    public static function php_throwable($error = null, ...$errors) : ?object
    {
        if (is_a($error, \Closure::class)) {
            $error = $error(...$errors);
        }

        if (
            is_a($error, \LogicException::class)
            || is_a($error, \RuntimeException::class)
        ) {
            return $error;
        }

        $throwErrors = static::php_throwable_args($error, ...$errors);

        $message = $throwErrors[ 'message' ] ?? __FUNCTION__;
        $code = $throwErrors[ 'code' ] ?? -1;
        $previous = $throwErrors[ 'previous' ] ?? null;

        return $previous
            ? new \RuntimeException($message, $code, $previous)
            : new \LogicException($message, $code);
    }

    /**
     * > gzhegow, парсит ошибки для передачи результата в конструктор исключения
     *
     * @return array{
     *     message: string,
     *     code: int,
     *     previous: string,
     *     messageData: array,
     *     messageObject: object,
     * }
     */
    public static function php_throwable_args($arg = null, ...$args) : array
    {
        $_message = null;
        $_code = null;
        $_previous = null;
        $_messageData = null;

        array_unshift($args, $arg);

        foreach ( $args as $v ) {
            if (is_int($v)) {
                $_code = $v;

                continue;
            }

            if (is_a($v, \Throwable::class)) {
                $_previous = $v;

                continue;
            }

            if (null !== ($_string = static::filter_string($v))) {
                $_message = $_string;

                continue;
            }

            if (
                is_array($v)
                || is_a($v, \stdClass::class)
            ) {
                $_messageData = (array) $v;

                if (isset($_messageData[ 0 ])) {
                    $_message = static::filter_string($_messageData[ 0 ]);
                }
            }
        }

        $_message = $_message ?? null;
        $_code = $_code ?? null;
        $_previous = $_previous ?? null;

        $_messageObject = null
            ?? ((null !== $_messageData) ? (object) $_messageData : null)
            ?? ((null !== $_message) ? (object) [ $_message ] : null);

        if (null !== $_messageData) {
            unset($_messageData[ 0 ]);

            $_messageData = $_messageData ?: null;
        }

        $result = [];
        $result[ 'message' ] = $_message;
        $result[ 'code' ] = $_code;
        $result[ 'previous' ] = $_previous;
        $result[ 'messageData' ] = $_messageData;
        $result[ 'messageObject' ] = $_messageObject;

        return $result;
    }


    public static function php_trigger_error_enabled(bool $enable = null) : bool
    {
        static $enabled;

        $enabled = $enable ?? $enabled ?? false;

        return $enabled;
    }

    public static function php_trigger_error($err, int $error_level = null, $result = null) // : mixed
    {
        $error_level = $error_level ?? E_USER_NOTICE;

        $error = is_array($err)
            ? (array) $err
            : [ $err ];

        if (static::php_trigger_error_enabled()) {
            trigger_error($error[ 0 ], $error_level);
        }

        return $result;
    }


    /**
     * @param callable|array|object|class-string     $mixed
     *
     * @param array{0: class-string, 1: string}|null $resultArray
     * @param callable|string|null                   $resultString
     *
     * @return array{0: class-string|object, 1: string}|null
     */
    public static function php_method_exists(
        $mixed, $method = null,
        array &$resultArray = null, string &$resultString = null
    ) : ?array
    {
        $resultArray = null;
        $resultString = null;

        $method = $method ?? '';

        $_class = null;
        $_object = null;
        $_method = null;
        if (is_object($mixed)) {
            $_object = $mixed;

        } elseif (is_array($mixed)) {
            $list = array_values($mixed);

            /** @noinspection PhpWrongStringConcatenationInspection */
            [ $classOrObject, $_method ] = $list + [ '', '' ];

            is_object($classOrObject)
                ? ($_object = $classOrObject)
                : ($_class = $classOrObject);

        } elseif (is_string($mixed)) {
            [ $_class, $_method ] = explode('::', $mixed) + [ '', '' ];

            $_method = $_method ?? $method;
        }

        if (isset($_method) && ! is_string($_method)) {
            return null;
        }

        if ($_object) {
            if ($_object instanceof \Closure) {
                return null;
            }

            if (method_exists($_object, $_method)) {
                $class = get_class($_object);

                $resultArray = [ $class, $_method ];
                $resultString = $class . '::' . $_method;

                return [ $_object, $_method ];
            }

        } elseif ($_class) {
            if (method_exists($_class, $_method)) {
                $resultArray = [ $_class, $_method ];
                $resultString = $_class . '::' . $_method;

                return [ $_class, $_method ];
            }
        }

        return null;
    }


    /**
     * > gzhegow, всегда возвращает публичные свойства объекта
     */
    public static function php_get_object_vars_public(object $object) : array
    {
        $vars = get_object_vars($object);

        return $vars;
    }

    /**
     * > gzhegow, всегда возвращает все (публичные и защищенные) свойства объекта
     */
    public static function php_get_object_vars(object $object) : array
    {
        $vars = (function () {
            return get_object_vars($this);
        })->call($object);

        return $vars;
    }


    public static function filter_str($value) : ?string
    {
        if (is_string($value)) {
            return $value;
        }

        if (
            (null === $value)
            || is_array($value)
            || is_resource($value)
        ) {
            return null;
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $_value = (string) $value;

                return $_value;
            }

            return null;
        }

        $_value = $value;
        $status = @settype($_value, 'string');

        if ($status) {
            return $_value;
        }

        return null;
    }

    public static function filter_string($value) : ?string
    {
        if (null === ($_value = static::filter_str($value))) {
            return null;
        }

        if ('' === $_value) {
            return null;
        }

        return $_value;
    }


    public static function filter_path(
        $value, array $optional = [],
        array &$pathinfo = null
    ) : ?string
    {
        $pathinfo = null;

        $optional[ 0 ] = $optional[ 'with_pathinfo' ] ?? $optional[ 0 ] ?? false;

        if (null === ($_value = static::filter_string($value))) {
            return null;
        }

        if (false !== strpos($_value, "\0")) {
            return null;
        }

        $withPathInfoResult = (bool) $optional[ 0 ];

        if ($withPathInfoResult) {
            try {
                $pathinfo = pathinfo($_value);
            }
            catch ( \Throwable $e ) {
                return null;
            }
        }

        return $_value;
    }

    public static function filter_dirpath(
        $value, array $optional = [],
        array &$pathinfo = null
    ) : ?string
    {
        $_value = static::filter_path(
            $value, $optional,
            $pathinfo
        );

        if (null === $_value) {
            return null;
        }

        if (file_exists($_value) && ! is_dir($_value)) {
            return null;
        }

        return $_value;
    }


    public static function filter_filename($value) : ?string
    {
        if (null === ($_value = static::filter_string($value))) {
            return null;
        }

        $forbidden = [ "\0", "/", "\\", DIRECTORY_SEPARATOR ];

        foreach ( $forbidden as $f ) {
            if (false !== strpos($_value, $f)) {
                return null;
            }
        }

        return $_value;
    }


    public static function filter_regex($regex) : ?string
    {
        if (null === ($_value = static::filter_string($regex))) {
            return null;
        }

        $before = error_reporting(0);
        $status = @preg_match($regex, '');
        error_reporting($before);

        if (false === $status) {
            return null;
        }

        return $_value;
    }


    /**
     * @param callable ...$fnExistsList
     */
    public static function filter_struct($value, bool $useRegex = null, ...$fnExistsList) : ?string
    {
        $useRegex = $useRegex ?? false;
        $fnExistsList = $fnExistsList ?: [ 'class_exists' ];

        if (is_object($value)) {
            return ltrim(get_class($value), '\\');
        }

        if (null === ($_value = static::filter_string($value))) {
            return null;
        }

        $_value = ltrim($_value, '\\');

        foreach ( $fnExistsList as $fn ) {
            if ($fn($_value)) {
                return $_value;
            }
        }

        if ($useRegex) {
            if (! preg_match(
                '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/',
                $_value
            )) {
                return null;
            }
        }

        return $_value;
    }

    public static function filter_class($value, bool $useRegex = null) : ?string
    {
        $_value = static::filter_struct($value, $useRegex, 'class_exists');

        if (null === $_value) {
            return null;
        }

        return $_value;
    }


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
    public static function array_int_index(array $array, array ...$arrays) : array
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
    public static function array_string_index(array $array, array ...$arrays) : array
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
}
