<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Store\RouterStore;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Cache\RouterCacheInterface;
use Gzhegow\Router\Core\Matcher\RouterMatcherInterface;
use Gzhegow\Router\Core\Invoker\RouterInvokerInterface;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherInterface;
use Gzhegow\Router\Core\UrlGenerator\RouterUrlGeneratorInterface;
use Gzhegow\Router\Core\Handler\Fallback\RouterGenericHandlerFallback;
use Gzhegow\Router\Core\Matcher\Contract\RouterMatcherContractInterface;
use Gzhegow\Router\Core\Handler\Middleware\RouterGenericHandlerMiddleware;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRouteContractInterface;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRequestContractInterface;


interface RouterInterface
{
    public function getConfig() : RouterConfig;


    public function getFactory() : RouterFactoryInterface;


    public function getCache() : RouterCacheInterface;

    public function getDispatcher() : RouterDispatcherInterface;

    public function getInvoker() : RouterInvokerInterface;

    public function getMatcher() : RouterMatcherInterface;

    public function getUrlGenerator() : RouterUrlGeneratorInterface;


    public function getStore() : RouterStore;


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

    public function registerMiddleware(RouterGenericHandlerMiddleware $middleware) : int;


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

    public function registerFallback(RouterGenericHandlerFallback $fallback) : int;



    public function commit() : RouterInterface;



    /**
     * @param mixed|RouterDispatcherRequestContractInterface $contract
     * @param array{ 0: array }|PipeContext                  $context
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

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatchByRequest(
        RouterDispatcherRequestContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    );

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatchByRoute(
        RouterDispatcherRouteContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    );


    public function hasRequestContract(?RouterDispatcherRequestContractInterface &$contract = null) : bool;

    public function getRequestContract() : RouterDispatcherRequestContractInterface;


    public function hasRouteContract(?RouterDispatcherRouteContractInterface &$contract = null) : bool;

    public function getRouteContract() : RouterDispatcherRouteContractInterface;


    public function getDispatchRequestMethod() : string;

    public function getDispatchRequestUri() : string;

    public function getDispatchRequestPath() : string;


    public function getDispatchRoute() : Route;

    public function getDispatchActionAttributes() : array;


    /**
     * @return RouterGenericHandlerMiddleware[]
     */
    public function getDispatchMiddlewareIndex() : array;

    /**
     * @return RouterGenericHandlerFallback[]
     */
    public function getDispatchFallbackIndex() : array;



    /**
     * @param int[] $idList
     *
     * @return Route[]
     */
    public function matchAllByIds(array $idList) : array;

    /**
     * @param int[] $idList
     */
    public function matchFirstByIds(array $idList) : ?Route;


    /**
     * @param (string|RouteName)[] $nameList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $nameList, ?bool $unique = null) : array;

    /**
     * @param (string|RouteName)[] $nameList
     */
    public function matchFirstByNames(array $nameList) : ?Route;


    /**
     * @param (string|RouteTag)[] $tagList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $tagList, ?bool $unique = null) : array;

    /**
     * @param (string|RouteTag)[] $tagList
     */
    public function matchFirstByTags(array $tagList) : ?Route;


    /**
     * @param array{
     *     name: string|false|null,
     *     tag: string|false|null,
     *     method: string|false|null,
     *     path: string|false|null,
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     *     3: string|false|null,
     * }[] $paramsList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByParams(array $paramsList, ?bool $unique = null) : array;

    /**
     * @param array{
     *     name: string|false|null,
     *     tag: string|false|null,
     *     method: string|false|null,
     *     path: string|false|null,
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     *     3: string|false|null,
     * }[] $paramsList
     */
    public function matchFirstByParams(array $paramsList) : ?Route;


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContractInterface $contract) : array;

    public function matchFirstByContract(RouterMatcherContractInterface $contract) : ?Route;



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
