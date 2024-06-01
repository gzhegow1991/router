<?php

namespace Gzhegow\Router\Contract;

use Gzhegow\Router\Route\Struct\HttpMethod;
use Gzhegow\Router\Exception\LogicException;
use function Gzhegow\Router\_err;
use function Gzhegow\Router\_php_dump;
use function Gzhegow\Router\_filter_path;


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
    public static function from($from) : object
    {
        if (null === ($instance = static::tryFrom($from))) {
            throw new LogicException([
                'Unknown `from`: ' . _php_dump($from)
            ]);
        }

        return $instance;
    }

    /**
     * @return static|null
     */
    public static function tryFrom($from) : ?object
    {
        $instance = null
            ?? static::fromStatic($from)
            ?? static::fromArray($from);

        return $instance;
    }

    /**
     * @return static|null
     */
    protected static function fromStatic($static) : ?object
    {
        if (! is_a($static, static::class)) {
            return _err([ 'The `from` should be instance of: ' . static::class, $static ]);
        }

        return $static;
    }

    /**
     * @return static|null
     */
    protected static function fromArray($array) : ?object
    {
        if (! is_array($array)) {
            return _err([ 'The `from` should be array', $array ]);
        }

        [ $httpMethod, $requestUri ] = $array;

        if (null === ($_httpMethod = HttpMethod::tryFrom($httpMethod))) {
            return _err([
                'The `from[0]` should be valid `httpMethod`: ' . _php_dump($httpMethod),
                $array,
            ]);
        }

        if (null === ($_requestUri = _filter_path($requestUri))) {
            return _err([
                'The `from[0]` should be valid `path`: ' . _php_dump($requestUri),
                $array,
            ]);
        }

        $instance = new static();
        $instance->httpMethod = $_httpMethod;
        $instance->requestUri = $_requestUri;

        return $instance;
    }
}
