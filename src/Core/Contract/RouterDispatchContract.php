<?php

namespace Gzhegow\Router\Core\Contract;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Exception\LogicException;
use Gzhegow\Router\Core\Route\Struct\HttpMethod;


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
     * @return static|bool|null
     */
    public static function from($from, array $refs = [])
    {
        $withErrors = array_key_exists(0, $refs);

        $refs[ 0 ] = $refs[ 0 ] ?? null;

        $instance = null
            ?? static::fromStatic($from, $refs)
            ?? static::fromArray($from, $refs);

        if (! $withErrors) {
            if (null === $instance) {
                throw $refs[ 0 ];
            }
        }

        return $instance;
    }

    /**
     * @return static|bool|null
     */
    public static function fromStatic($from, array $refs = [])
    {
        if ($from instanceof static) {
            return Lib::refsResult($refs, $from);
        }

        return Lib::refsError(
            $refs,
            new LogicException(
                [ 'The `from` should be instance of: ' . static::class, $from ]
            )
        );
    }

    /**
     * @return static|bool|null
     */
    public static function fromArray($from, array $refs = [])
    {
        if (! is_array($from)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from` should be array', $from ]
                )
            );
        }

        [ $httpMethod, $requestUri ] = $from;

        $httpMethodObject = HttpMethod::from($httpMethod);

        if (! Lib::type()->path($requestUriString, $requestUri)) {
            return Lib::refsError(
                $refs,
                new LogicException(
                    [ 'The `from[0]` should be valid `path`', $requestUri, $from ]
                )
            );
        }

        $instance = new static();
        $instance->httpMethod = $httpMethodObject;
        $instance->requestUri = $requestUriString;

        return Lib::refsResult($refs, $instance);
    }
}
