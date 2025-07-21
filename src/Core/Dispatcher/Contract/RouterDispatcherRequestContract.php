<?php

namespace Gzhegow\Router\Core\Dispatcher\Contract;

use Gzhegow\Lib\Modules\Type\Ret;
use Gzhegow\Router\Core\Route\Struct\HttpPath;
use Gzhegow\Router\Core\Route\Struct\HttpMethod;


class RouterDispatcherRequestContract implements RouterDispatcherRequestContractInterface
{
    /**
     * @var HttpMethod
     */
    protected $requestHttpMethod;
    /**
     * @var HttpPath
     */
    protected $requestHttpPath;


    private function __construct()
    {
    }


    /**
     * @return static|Ret<static>
     */
    public static function from($from, ?array $fallback = null)
    {
        $ret = Ret::new();

        $instance = null
            ?? static::fromStatic($from)->orNull($ret)
            ?? static::fromArray($from)->orNull($ret);

        if ($ret->isFail()) {
            return Ret::throw($fallback, $ret);
        }

        return Ret::ok($fallback, $instance);
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromStatic($from, ?array $fallback = null)
    {
        if ($from instanceof static) {
            return Ret::ok($fallback, $from);
        }

        return Ret::throw(
            $fallback,
            [ 'The `from` should be instance of: ' . static::class, $from ],
            [ __FILE__, __LINE__ ]
        );
    }

    /**
     * @return static|Ret<static>
     */
    public static function fromArray($from, ?array $fallback = null)
    {
        if (! is_array($from)) {
            return Ret::throw(
                $fallback,
                [ 'The `from` should be array', $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        $requestMethod = $from[ 'requestMethod' ] ?? $from[ 0 ];
        $requestPath = $from[ 'requestPath' ] ?? $from[ 1 ];

        if (! HttpMethod::from($requestMethod)->isOk([ &$requestMethodObject, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        if (! HttpPath::from($requestPath)->isOk([ &$requestPathObject, &$ret ])) {
            return Ret::throw($fallback, $ret);
        }

        $instance = new static();
        $instance->requestHttpMethod = $requestMethodObject;
        $instance->requestHttpPath = $requestPathObject;

        return Ret::ok($fallback, $instance);
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


    public function hasRequestQuery(?array &$refQuery = null) : bool
    {
        return $this->requestHttpPath->hasQuery($refQuery);
    }

    public function getRequestQuery() : array
    {
        return $this->requestHttpPath->getQuery();
    }


    public function hasRequestQueryString(?string &$refQueryString = null) : bool
    {
        return $this->requestHttpPath->hasQueryString($refQueryString);
    }

    public function getRequestQueryString() : string
    {
        return $this->requestHttpPath->getQueryString();
    }


    public function hasRequestFragment(?string &$refFragment = null) : bool
    {
        return $this->requestHttpPath->hasQueryString($refFragment);
    }

    public function getRequestFragment() : string
    {
        return $this->requestHttpPath->getFragment();
    }
}
