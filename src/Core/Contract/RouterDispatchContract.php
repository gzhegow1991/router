<?php

namespace Gzhegow\Router\Core\Contract;

use Gzhegow\Lib\Lib;
use Gzhegow\Lib\Modules\Php\Result\Ret;
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
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function from($from, $ret = null)
    {
        $retCur = Result::asValue();

        $instance = null
            ?? static::fromStatic($from, $retCur)
            ?? static::fromArray($from, $retCur);

        if ($retCur->isErr()) {
            return Result::err($ret, $retCur);
        }

        return Result::ok($ret, $instance);
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromStatic($from, $ret = null)
    {
        if ($from instanceof static) {
            return Result::ok($ret, $from);
        }

        return Result::err(
            $ret,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @param Ret $ret
     *
     * @return static|bool|null
     */
    public static function fromArray($from, $ret = null)
    {
        if (! is_array($from)) {
            return Result::err(
                $ret,
                [ 'The `from` should be array', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        [ $httpMethod, $requestUri ] = $from;

        $httpMethodObject = HttpMethod::from($httpMethod);

        if (! Lib::type()->path($requestUriString, $requestUri)) {
            return Result::err(
                $ret,
                [ 'The `from[0]` should be valid `path`', $requestUri, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $instance = new static();
        $instance->httpMethod = $httpMethodObject;
        $instance->requestUri = $requestUriString;

        return Result::ok($ret, $instance);
    }
}
