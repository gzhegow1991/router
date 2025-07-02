<?php

namespace Gzhegow\Router\Core\Dispatcher;

use Gzhegow\Lib\Modules\Php\Result\Ret;
use Gzhegow\Lib\Modules\Php\Result\Result;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Core\Route\Struct\HttpMethod;


class RouterDispatcherContract
{
    /**
     * @var HttpMethod
     */
    public $requestMethod;
    /**
     * @var Path
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

        $requestMethod = $from[ 'requestMethod' ] ?? $from[ 0 ];
        $requestUri = $from[ 'requestUri' ] ?? $from[ 1 ];

        $requestUri = explode('?', $requestUri, 2)[ 0 ];

        $requestMethodObject = HttpMethod::from($requestMethod);
        $requestUriObject = Path::from($requestUri);

        $instance = new static();
        $instance->requestMethod = $requestMethodObject;
        $instance->requestUri = $requestUriObject;

        return Result::ok($ret, $instance);
    }


    public function getRequestMethod() : HttpMethod
    {
        return $this->requestMethod;
    }

    public function getRequestUri() : Path
    {
        return $this->requestUri;
    }
}
