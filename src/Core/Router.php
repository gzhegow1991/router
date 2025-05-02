<?php

namespace Gzhegow\Router\Core;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Router\Core\Contract\RouterMatchContract;
use Gzhegow\Router\Core\Contract\RouterDispatchContract;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


class Router
{
    const PATTERN_ENCLOSURE = '{}';

    const TRAILING_SLASH_NEVER  = -1;
    const TRAILING_SLASH_AS_IS  = 0;
    const TRAILING_SLASH_ALWAYS = 1;

    const LIST_TRAILING_SLASH = [
        self::TRAILING_SLASH_NEVER  => true,
        self::TRAILING_SLASH_AS_IS  => true,
        self::TRAILING_SLASH_ALWAYS => true,
    ];


    public static function cacheClear() : RouterInterface
    {
        return static::$facade->cacheClear();
    }

    /**
     * @param callable $fn
     */
    public static function cacheRemember($fn) : RouterInterface
    {
        return static::$facade->cacheRemember($fn);
    }


    public static function newBlueprint(RouteBlueprint $from = null) : RouteBlueprint
    {
        return static::$facade->newBlueprint($from);
    }


    /**
     * @param string|null                                    $path
     * @param string|string[]|null                           $httpMethods
     * @param callable|object|array|class-string|string|null $action
     * @param string|null                                    $name
     * @param string|string[]|null                           $tags
     */
    public static function blueprint(
        RouteBlueprint $from = null,
        $path = null, $httpMethods = null, $action = null, $name = null, $tags = null
    ) : RouteBlueprint
    {
        return static::$facade->blueprint(
            $from,
            $path, $httpMethods, $action, $name, $tags
        );
    }


    public static function group(RouteBlueprint $from = null) : RouteGroup
    {
        return static::$facade->group($from);
    }


    /**
     * @param string                                    $path
     * @param string|string[]                           $httpMethods
     * @param callable|object|array|class-string|string $action
     *
     * @param string|null                               $name
     * @param string|string[]|null                      $tags
     */
    public static function route(
        $path, $httpMethods, $action,
        $name = null, $tags = null
    ) : RouteBlueprint
    {
        return static::$facade->route(
            $path, $httpMethods, $action,
            $name, $tags
        );
    }

    public static function addRoute(RouteBlueprint $routeBlueprint) : RouterInterface
    {
        return static::$facade->addRoute($routeBlueprint);
    }


    /**
     * @param string $pattern
     * @param string $regex
     */
    public static function pattern($pattern, $regex) : RouterInterface
    {
        return static::$facade->pattern($pattern, $regex);
    }


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $middleware
     */
    public static function middlewareOnPath($path, $middleware) : RouterInterface
    {
        return static::$facade->middlewareOnPath($path, $middleware);
    }

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $middleware
     */
    public static function middlewareOnTag($tag, $middleware) : RouterInterface
    {
        return static::$facade->middlewareOnTag($tag, $middleware);
    }


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $fallback
     */
    public static function fallbackOnPath($path, $fallback) : RouterInterface
    {
        return static::$facade->fallbackOnPath($path, $fallback);
    }

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $fallback
     */
    public static function fallbackOnTag($tag, $fallback) : RouterInterface
    {
        return static::$facade->fallbackOnTag($tag, $fallback);
    }


    public static function commit() : RouterInterface
    {
        return static::$facade->commit();
    }


    /**
     * @param int|int[] $ids
     *
     * @return Route[]
     */
    public static function matchAllByIds($ids) : array
    {
        return static::$facade->matchAllByIds($ids);
    }

    /**
     * @param int|int[] $ids
     */
    public static function matchFirstByIds($ids) : ?Route
    {
        return static::$facade->matchFirstByIds($ids);
    }


    /**
     * @param string|string[] $names
     *
     * @return Route[]|Route[][]
     */
    public static function matchAllByNames($names, bool $unique = null) : array
    {
        return static::$facade->matchAllByNames($names, $unique);
    }

    /**
     * @param string|string[] $names
     */
    public static function matchFirstByNames($names) : ?Route
    {
        return static::$facade->matchFirstByNames($names);
    }


    /**
     * @param string|string[] $tags
     *
     * @return Route[]|Route[][]
     */
    public static function matchAllByTags($tags, bool $unique = null) : array
    {
        return static::$facade->matchAllByTags($tags, $unique);
    }

    /**
     * @param string|string[] $tags
     */
    public static function matchFirstByTags($tags) : ?Route
    {
        return static::$facade->matchFirstByTags($tags);
    }


    /**
     * @return Route[]
     */
    public static function matchByContract(RouterMatchContract $contract) : array
    {
        return static::$facade->matchByContract($contract);
    }


    /**
     * @return mixed
     * @throws DispatchException
     */
    public static function dispatch(RouterDispatchContract $contract, $input = null, $context = null)
    {
        return static::$facade->dispatch($contract, $input, $context);
    }


    /**
     * @param Route|Route[]|string|string[] $routes
     *
     * @return string[]
     */
    public static function urls($routes, array $attributes = []) : array
    {
        return static::$facade->urls($routes, $attributes);
    }

    /**
     * @param Route|string $route
     */
    public static function url($route, array $attributes = []) : string
    {
        return static::$facade->url($route, $attributes);
    }


    /**
     * @return int[]
     */
    public static function registerRouteGroup(RouteGroup $routeGroup) : array
    {
        return static::$facade->registerRouteGroup($routeGroup);
    }


    public static function registerRoute(Route $route) : int
    {
        return static::$facade->registerRoute($route);
    }

    public static function registerPattern(RouterPattern $pattern) : string
    {
        return static::$facade->registerPattern($pattern);
    }

    public static function registerMiddleware(GenericHandlerMiddleware $middleware) : int
    {
        return static::$facade->registerMiddleware($middleware);
    }

    public static function registerFallback(GenericHandlerFallback $fallback) : int
    {
        return static::$facade->registerFallback($fallback);
    }


    public static function setFacade(RouterInterface $facade) : ?RouterInterface
    {
        $last = static::$facade;

        static::$facade = $facade;

        return $last;
    }

    /**
     * @var RouterInterface
     */
    protected static $facade;
}
