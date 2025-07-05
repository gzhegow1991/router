<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Route\Struct\RouteNameTag;
use Gzhegow\Router\Core\Cache\RouterCacheInterface;
use Gzhegow\Router\Core\Matcher\RouterMatcherContract;
use Gzhegow\Router\Core\Matcher\RouterMatcherInterface;
use Gzhegow\Router\Core\Invoker\RouterInvokerInterface;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Collection\RouterPatternCollection;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherInterface;
use Gzhegow\Router\Core\Collection\RouterMiddlewareCollection;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Core\UrlGenerator\RouterUrlGeneratorInterface;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


interface RouterInterface
{
    public function getRouterFactory() : RouterFactoryInterface;


    public function getRouterCache() : RouterCacheInterface;

    public function getRouterDispatcher() : RouterDispatcherInterface;

    public function getRouterInvoker() : RouterInvokerInterface;

    public function getRouterMatcher() : RouterMatcherInterface;

    public function getRouterUrlGenerator() : RouterUrlGeneratorInterface;


    public function getRouteCollection() : RouterRouteCollection;

    public function getPatternCollection() : RouterPatternCollection;

    public function getMiddlewareCollection() : RouterMiddlewareCollection;

    public function getFallbackCollection() : RouterFallbackCollection;


    public function getRootRouterGroup() : RouteGroup;

    public function getRootRouterNode() : RouterNode;


    public function getConfig() : RouterConfig;


    public function cacheClear() : RouterInterface;

    /**
     * @param callable $fn
     */
    public function cacheRemember($fn, ?bool $commit = null) : RouterInterface;


    public function newBlueprint(?RouteBlueprint $from = null) : RouteBlueprint;

    /**
     * @param string|null                                    $path
     * @param string|string[]|null                           $httpMethods
     * @param callable|object|array|class-string|string|null $action
     *
     * @param string|null                                    $name
     * @param string|string[]|null                           $tags
     */
    public function blueprint(
        ?RouteBlueprint $from = null,
        $path = null, $httpMethods = null, $action = null, $name = null, $tags = null
    ) : RouteBlueprint;


    public function group(?RouteBlueprint $from = null) : RouteGroup;

    /**
     * @return int[]
     */
    public function registerRouteGroup(RouteGroup $routeGroup) : array;


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

    public function registerRoute(Route $route) : int;


    /**
     * @param string|RouterPattern $pattern
     * @param string|null          $regex
     */
    public function pattern($pattern, $regex = null) : RouterInterface;

    public function registerPattern(RouterPattern $pattern) : string;


    /**
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRouteId($routeId, $middleware) : RouterInterface;

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRoutePath($routePath, $middleware) : RouterInterface;

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRouteTag($routeTag, $middleware) : RouterInterface;

    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int;


    /**
     * @param int                                       $routeId
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRouteId($routeId, $fallback) : RouterInterface;

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRoutePath($routePath, $fallback) : RouterInterface;

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRouteTag($routeTag, $fallback) : RouterInterface;

    public function registerFallback(GenericHandlerFallback $fallback) : int;



    public function commit() : RouterInterface;



    /**
     * @param mixed|RouterDispatcherContract $contract
     * @param array{ 0: array }|PipeContext  $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        $contract,
        $input = null,
        $context = null,
        array $args = []
    );


    public function getDispatchContract() : RouterDispatcherContract;

    public function getDispatchRequestMethod() : string;

    public function getDispatchRequestUri() : string;

    public function getDispatchRequestPath() : string;


    public function getDispatchRoute() : Route;

    public function getDispatchActionAttributes() : array;



    /**
     * @param int[] $routeIds
     *
     * @return Route[]
     */
    public function matchAllByIds(array $routeIds) : array;

    /**
     * @param int[] $routeIds
     */
    public function matchFirstByIds(array $routeIds) : ?Route;


    /**
     * @param (string|RouteName)[] $routeNames
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $routeNames, ?bool $unique = null) : array;

    /**
     * @param (string|RouteName)[] $routeNames
     */
    public function matchFirstByNames(array $routeNames) : ?Route;


    /**
     * @param (string|RouteTag)[] $routeTags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $routeTags, ?bool $unique = null) : array;

    /**
     * @param (string|RouteTag)[] $routeTags
     */
    public function matchFirstByTags(array $routeTags) : ?Route;


    /**
     * @param (array{ 0: string, 1: string }|RouteNameTag)[] $routeNameTags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNameTags(array $routeNameTags, ?bool $unique = null) : array;

    /**
     * @param (array{ 0: string, 1: string }|RouteNameTag)[] $routeNameTags
     */
    public function matchFirstByNameTags(array $routeNameTags) : ?Route;


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContract $contract) : array;



    /**
     * @param (string|Route)[] $routes
     *
     * @return string[]
     */
    public function urls(array $routes, array $attributes = []) : array;

    /**
     * @param Route|string $route
     */
    public function url($route, array $attributes = []) : string;
}
