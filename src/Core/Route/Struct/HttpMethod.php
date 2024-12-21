<?php

namespace Gzhegow\Router\Core\Route\Struct;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Exception\LogicException;


class HttpMethod
{
    const METHOD_CONNECT = 'CONNECT';
    const METHOD_DELETE  = 'DELETE';
    const METHOD_GET     = 'GET';
    const METHOD_HEAD    = 'HEAD';
    const METHOD_OPTIONS = 'OPTIONS';
    const METHOD_PATCH   = 'PATCH';
    const METHOD_POST    = 'POST';
    const METHOD_PUT     = 'PUT';
    const METHOD_TRACE   = 'TRACE';

    const LIST_METHOD = [
        self::METHOD_CONNECT => true,
        self::METHOD_DELETE  => true,
        self::METHOD_GET     => true,
        self::METHOD_HEAD    => true,
        self::METHOD_OPTIONS => true,
        self::METHOD_PATCH   => true,
        self::METHOD_POST    => true,
        self::METHOD_PUT     => true,
        self::METHOD_TRACE   => true,
    ];


    /**
     * @var string
     */
    public $value;


    private function __construct()
    {
    }


    /**
     * @return static
     */
    public static function from($from) // : static
    {
        $instance = static::tryFrom($from, $error);

        if (null === $instance) {
            throw $error;
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from, \Throwable &$last = null) // : ?static
    {
        $last = null;

        Lib::php_errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromString($from);

        $errors = Lib::php_errors_end($b);

        if (null === $instance) {
            foreach ( $errors as $error ) {
                $last = new LogicException($error, null, $last);
            }
        }

        return $instance;
    }


    /**
     * @return static|null
     */
    public static function tryFromInstance($instance) // : ?static
    {
        if (! is_a($instance, static::class)) {
            return Lib::php_error(
                [ 'The `from` should be instance of: ' . static::class, $instance ]
            );
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFromString($string) // : ?static
    {
        if (null === ($_string = Lib::parse_string_not_empty($string))) {
            return Lib::php_error(
                [ 'The `from` should be non-empty string', $string ]
            );
        }

        $_string = strtoupper($_string);

        if (! isset(static::LIST_METHOD[ $_string ])) {
            return Lib::php_error(
                [
                    'The `from` should be one of: ' . implode(',', array_keys(static::LIST_METHOD)),
                    $string,
                ]
            );
        }

        $instance = new static();
        $instance->value = $_string;

        return $instance;
    }


    public function __toString()
    {
        return $this->value;
    }


    public function getValue() : string
    {
        return $this->value;
    }
}
