<?php

namespace Gzhegow\Router\Contract;

use Gzhegow\Router\Lib;
use Gzhegow\Router\Route\Struct\HttpMethod;
use Gzhegow\Router\Exception\LogicException;


class RouterDispatchContract
{
    /**
     * @var HttpMethod
     */
    public $httpMethod;
    /**
     * @var string
     */
    public $requestUri;


    private function __construct()
    {
    }


    /**
     * @return static
     */
    public static function from($from) : self
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
    public static function tryFrom($from, \Throwable &$last = null) : ?self
    {
        $last = null;

        Lib::php_errors_start($b);

        $instance = null
            ?? static::tryFromInstance($from)
            ?? static::tryFromArray($from);

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
    public static function tryFromInstance($instance) : ?self
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
    public static function tryFromArray($array) : ?self
    {
        if (! is_array($array)) {
            return Lib::php_error(
                [ 'The `from` should be array', $array ]
            );
        }

        [ $httpMethod, $requestUri ] = $array;

        if (null === ($_httpMethod = HttpMethod::tryFrom($httpMethod))) {
            return Lib::php_error(
                [
                    'The `from[0]` should be valid `httpMethod`: ' . Lib::debug_dump($httpMethod),
                    $array,
                ]
            );
        }

        if (null === ($_requestUri = Lib::parse_path($requestUri))) {
            return Lib::php_error(
                [
                    'The `from[0]` should be valid `path`: ' . Lib::debug_dump($requestUri),
                    $array,
                ]
            );
        }

        $instance = new static();
        $instance->httpMethod = $_httpMethod;
        $instance->requestUri = $_requestUri;

        return $instance;
    }
}
