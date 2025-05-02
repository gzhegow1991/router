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


interface RouterInterface
{
    public function cacheClear() : RouterInterface;

    /**
     * @param callable $fn
     */
    public function cacheRemember($fn) : RouterInterface;


    public function newBlueprint(RouteBlueprint $from = null) : RouteBlueprint;

    /**
     * @param string|null                                    $path
     * @param string|string[]|null                           $httpMethods
     * @param callable|object|array|class-string|string|null $action
     * @param string|null                                    $name
     * @param string|string[]|null                           $tags
     */
    public function blueprint(
        RouteBlueprint $from = null,
        $path = null, $httpMethods = null, $action = null, $name = null, $tags = null
    ) : RouteBlueprint;


    public function group(RouteBlueprint $from = null) : RouteGroup;


    /**
     * @param string                                    $path
     * @param string|string[]                           $httpMethods
     * @param callable|object|array|class-string|string $action
     *
     * @param string|null                               $name
     * @param string|string[]|null                      $tags
     */
    public function route(
        $path, $httpMethods, $action,
        $name = null, $tags = null
    ) : RouteBlueprint;

    public function addRoute(RouteBlueprint $routeBlueprint) : RouterInterface;


    /**
     * @param string $pattern
     * @param string $regex
     */
    public function pattern($pattern, $regex) : RouterInterface;


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnPath($path, $middleware) : RouterInterface;

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnTag($tag, $middleware) : RouterInterface;


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnPath($path, $fallback) : RouterInterface;

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnTag($tag, $fallback) : RouterInterface;


    public function commit() : RouterInterface;


    /**
     * @param int|int[] $ids
     *
     * @return Route[]
     */
    public function matchAllByIds($ids) : array;

    public function matchFirstByIds($ids) : ?Route;


    /**
     * @param string|string[] $names
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames($names, bool $unique = null) : array;

    public function matchFirstByNames($names) : ?Route;


    /**
     * @param string|string[] $tags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags($tags, bool $unique = null) : array;

    public function matchFirstByTags($tags) : ?Route;


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatchContract $contract) : array;


    /**
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(RouterDispatchContract $contract, $input = null, $context = null);


    /**
     * @param Route|Route[]|string|string[] $routes
     *
     * @return string[]
     */
    public function urls($routes, array $attributes = []) : array;

    /**
     * @param Route|string $route
     */
    public function url($route, array $attributes = []) : string;


    /**
     * @return int[]
     */
    public function registerRouteGroup(RouteGroup $routeGroup) : array;


    public function registerRoute(Route $route) : int;

    public function registerPattern(RouterPattern $pattern) : string;

    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int;

    public function registerFallback(GenericHandlerFallback $fallback) : int;
}
