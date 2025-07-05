<?php

namespace Gzhegow\Router\Core\Dispatcher;

use Gzhegow\Lib\Modules\Php\Result\Result;
use Gzhegow\Router\Core\Route\Struct\HttpPath;
use Gzhegow\Router\Core\Route\Struct\HttpMethod;


class RouterDispatcherContract
{
    /**
     * @var HttpMethod
     */
    public $requestHttpMethod;
    /**
     * @var HttpPath
     */
    public $requestHttpPath;


    private function __construct()
    {
    }


    /**
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
        $requestPath = $from[ 'requestPath' ] ?? $from[ 1 ];

        $requestMethodObject = HttpMethod::from($requestMethod);
        $requestPathObject = HttpPath::from($requestPath);

        $instance = new static();
        $instance->requestHttpMethod = $requestMethodObject;
        $instance->requestHttpPath = $requestPathObject;

        return Result::ok($ret, $instance);
    }


    public function getRequestHttpMethod() : HttpMethod
    {
        return $this->requestHttpMethod;
    }

    public function getRequestHttpPath() : HttpPath
    {
        return $this->requestHttpPath;
    }


    public function getRequestMethod() : string
    {
        return $this->requestHttpMethod->getValue();
    }


    public function getRequestUri() : string
    {
        return $this->requestHttpPath->getValue();
    }

    public function getRequestPath() : string
    {
        return $this->requestHttpPath->getPath();
    }
}
