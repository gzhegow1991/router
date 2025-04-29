<?php

namespace Gzhegow\Router\Core\Contract;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Php\Result\Result;
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
    public static function from($from, $ctx = null)
    {
        Result::parse($cur);

        $instance = null
            ?? static::fromStatic($from, $cur)
            ?? static::fromArray($from, $cur);

        if ($cur->isErr()) {
            return Result::err($ctx, $cur);
        }

        return Result::ok($ctx, $instance);
    }

    /**
     * @return static|bool|null
     */
    public static function fromStatic($from, $ctx = null)
    {
        if ($from instanceof static) {
            return Result::ok($ctx, $from);
        }

        return Result::err(
            $ctx,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @return static|bool|null
     */
    public static function fromArray($from, $ctx = null)
    {
        if (! is_array($from)) {
            return Result::err(
                $ctx,
                [ 'The `from` should be array', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        [ $httpMethod, $requestUri ] = $from;

        $httpMethodObject = HttpMethod::from($httpMethod);

        if (! Lib::type()->path($requestUriString, $requestUri)) {
            return Result::err(
                $ctx,
                [ 'The `from[0]` should be valid `path`', $requestUri, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->httpMethod = $httpMethodObject;
        $instance->requestUri = $requestUriString;

        return Result::ok($ctx, $instance);
    }
}
