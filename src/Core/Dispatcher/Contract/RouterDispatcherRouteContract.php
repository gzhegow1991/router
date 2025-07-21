<?php

namespace Gzhegow\Router\Core\Dispatcher\Contract;

use Gzhegow\Lib\Modules\Type\Ret;
use Gzhegow\Router\Core\Route\Route;


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

        $fromRoute = $from[ 'route' ] ?? $from[ 0 ];
        $fromRouteAttributes = $from[ 'routeAttributes' ] ?? $from[ 1 ] ?? [];

        if (! ($fromRoute instanceof Route)) {
            return Ret::throw(
                $fallback,
                [ 'The `from[0]` should be instance of: ' . Route::class, $from ],
                [ __FILE__, __LINE__ ]
            );
        }

        if ([] !== $fromRouteAttributes) {
            if (! is_array($fromRouteAttributes)) {
                return Ret::throw(
                    $fallback,
                    [ 'The `from[1]` should be array', $from ],
                    [ __FILE__, __LINE__ ]
                );
            }
        }

        $instance = new static();
        $instance->route = $fromRoute;
        $instance->routeAttributes = $fromRouteAttributes;

        return Ret::ok($fallback, $instance);
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
