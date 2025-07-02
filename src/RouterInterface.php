<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Core\Cache\RouterCacheInterface;
use Gzhegow\Router\Core\Matcher\RouterMatcherContract;
use Gzhegow\Router\Core\Matcher\RouterMatcherInterface;
use Gzhegow\Router\Core\Invoker\RouterInvokerInterface;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Core\Collection\RouterPatternCollection;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
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
     * @param string $pattern
     * @param string $regex
     */
    public function pattern($pattern, $regex) : RouterInterface;

    public function registerPattern(RouterPattern $pattern) : string;


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

    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int;


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

    public function getDispatchRoute() : Route;

    public function getDispatchActionAttributes() : array;


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
    public function matchAllByNames($names, ?bool $unique = null) : array;

    public function matchFirstByNames($names) : ?Route;

    /**
     * @param string|string[] $tags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags($tags, ?bool $unique = null) : array;

    public function matchFirstByTags($tags) : ?Route;

    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContract $contract) : array;


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
}
