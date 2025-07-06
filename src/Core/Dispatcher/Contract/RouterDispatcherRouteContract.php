<?php

namespace Gzhegow\Router\Core\Dispatcher\Contract;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Lib\Modules\Php\Result\Result;


class RouterDispatcherRouteContract implements RouterDispatcherRouteContractInterface
{
    /**
     * @var Route
     */
    protected $route;
    /**
     * @var array
     */
    protected $routeAttributes;


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

        $fromRoute = $from[ 'route' ] ?? $from[ 0 ];
        $fromRouteAttributes = $from[ 'routeAttributes' ] ?? $from[ 1 ] ?? [];

        if (! ($fromRoute instanceof Route)) {
            return Result::err(
                $ret,
                [ 'The `from[0]` should be instance of: ' . Route::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if ([] !== $fromRouteAttributes) {
            if (! is_array($fromRouteAttributes)) {
                return Result::err(
                    $ret,
                    [ 'The `from[1]` should be array', $from ],
                    [ __FILE__, __LINE__ ]
                );
            }
        }

        $instance = new static();
        $instance->route = $fromRoute;
        $instance->routeAttributes = $fromRouteAttributes;

        return Result::ok($ret, $instance);
    }


    /**
     * @return Route
     */
    public function getRoute() : Route
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function getRouteAttributes() : array
    {
        return $this->routeAttributes;
    }
}
