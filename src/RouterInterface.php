<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Pattern\Pattern;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Contract\RouterMatchContract;
use Gzhegow\Router\Contract\RouterDispatchContract;
use Gzhegow\Router\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Handler\Middleware\GenericHandlerMiddleware;


interface RouterInterface
{
    public function getConfig() : RouterConfig;

    /**
     * @param callable $fn
     *
     * @return static
     */
    public function setConfig($fn); // : static

    /**
     * @return static
     */
    public function resetConfig(); // : static


    /**
     * @return static
     */
    public function cacheClear(); // : static

    /**
     * @param callable $fn
     *
     * @return static
     */
    public function cacheRemember($fn); // : static


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

    /**
     * @return static
     */
    public function addRoute(RouteBlueprint $routeBlueprint); // : static


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


    /**
     * @param string $pattern
     * @param string $regex
     */
    public function pattern($pattern, $regex);


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnPath($path, $middleware);

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnTag($tag, $middleware);


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnPath($path, $fallback);

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnTag($tag, $fallback);


    /**
     * @return static
     */
    public function commit(); // : static


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
     * @throws \Throwable
     */
    public function dispatch(RouterDispatchContract $contract, $input = null, $context = null);


    /**
     * @param Route|Route[]|string|string[] $routes
     *
     * @return string[]
     */
    public function urls($routes, array $attributes = []) : array;


    /**
     * @return int[]
     */
    public function registerRouteGroup(RouteGroup $routeGroup) : array;

    public function registerRoute(Route $route) : int;

    public function registerPattern(Pattern $pattern) : string;

    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int;

    public function registerFallback(GenericHandlerFallback $fallback) : int;
}
