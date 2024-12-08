<?php

namespace Gzhegow\Router\Lib\Traits;

trait PhpTrait
{
    /**
     * @return object{ errors: array }
     */
    public static function php_errors() : object
    {
        static $stack;

        $stack = $stack
            ?? new class {
                public $errors = [];
            };

        return $stack;
    }

    /**
     * @return object{ list: array }
     */
    public static function php_errors_current() : object
    {
        $stack = static::php_errors();

        $errors = end($stack->errors);

        return $errors;
    }

    /**
     * @return object{ list: array }
     */
    public static function php_errors_new() : object
    {
        $errors = new class {
            public $list = [];
        };

        return $errors;
    }

    /**
     * @return object{ list: array }
     */
    public static function php_errors_start(object &$errors = null) : object
    {
        $stack = static::php_errors();

        $errors = static::php_errors_new();
        $stack->errors[] = $errors;

        return $errors;
    }

    public static function php_errors_end(?object $until) : array
    {
        $stack = static::php_errors();

        $errors = static::php_errors_new();

        while ( count($stack->errors) ) {
            $current = array_pop($stack->errors);

            foreach ( $current->list as $error ) {
                $errors->list[] = $error;
            }

            if ($current === $until) {
                break;
            }
        }

        return $errors->list;
    }

    public static function php_error($error, $result = null) // : mixed
    {
        $current = static::php_errors_current();

        $current->list[] = $error;

        return $result;
    }


    public static function php_throwable_args(...$args) : array
    {
        $len = count($args);

        $messageList = null;
        $codeList = null;
        $previousList = null;
        $messageCodeList = null;
        $messageDataList = null;

        $__unresolved = [];

        for ( $i = 0; $i < $len; $i++ ) {
            $arg = $args[ $i ];

            if (is_a($arg, \Throwable::class)) {
                $previousList[ $i ] = $arg;

                continue;
            }

            if (
                is_array($arg)
                || is_a($arg, \stdClass::class)
            ) {
                $messageData = (array) $arg;

                $messageString = isset($messageData[ 0 ])
                    ? (string) $messageData[ 0 ]
                    : '';

                if ('' !== $messageString) {
                    unset($messageData[ 0 ]);

                    $messageList[ $i ] = $messageString;
                }

                $messageDataList[ $i ] = $messageData;

                continue;
            }

            if (is_int($arg)) {
                $codeList[ $i ] = $arg;

                continue;
            }

            if ('' !== ($vString = (string) $arg)) {
                $messageList[ $i ] = $vString;

                continue;
            }

            $__unresolved[ $i ] = $arg;
        }

        for ( $i = 0; $i < $len; $i++ ) {
            if (isset($messageList[ $i ])) {
                if (preg_match('/^[a-z](?!.*\s)/i', $messageList[ $i ])) {
                    $messageCodeList[ $i ] = strtoupper($messageList[ $i ]);
                }
            }
        }

        $result = [];

        $result[ 'messageList' ] = $messageList;
        $result[ 'codeList' ] = $codeList;
        $result[ 'previousList' ] = $previousList;
        $result[ 'messageCodeList' ] = $messageCodeList;
        $result[ 'messageDataList' ] = $messageDataList;

        $messageDataList = $messageDataList ?? [];

        $message = $messageList ? end($messageList) : null;
        $code = $codeList ? end($codeList) : null;
        $previous = $previousList ? end($previousList) : null;
        $messageCode = $messageCodeList ? end($messageCodeList) : null;

        $messageData = $messageDataList
            ? array_replace(...$messageDataList)
            : [];

        $messageObject = (object) ([ $message ] + $messageData);

        $result[ 'message' ] = $message;
        $result[ 'code' ] = $code;
        $result[ 'previous' ] = $previous;
        $result[ 'messageCode' ] = $messageCode;
        $result[ 'messageData' ] = $messageData;

        $result[ 'messageObject' ] = $messageObject;

        $result[ '__unresolved' ] = $__unresolved;

        return $result;
    }
}
