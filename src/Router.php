<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Store\RouterStore;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
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


    private function __construct()
    {
    }


    public static function getConfig() : RouterConfig
    {
        return static::$facade->getConfig();
    }


    public static function getFactory() : RouterFactoryInterface
    {
        return static::$facade->getFactory();
    }


    public static function getCache() : RouterCacheInterface
    {
        return static::$facade->getCache();
    }

    public static function getDispatcher() : RouterDispatcherInterface
    {
        return static::$facade->getDispatcher();
    }

    public static function getInvoker() : RouterInvokerInterface
    {
        return static::$facade->getInvoker();
    }

    public static function getMatcher() : RouterMatcherInterface
    {
        return static::$facade->getMatcher();
    }

    public static function getUrlGenerator() : RouterUrlGeneratorInterface
    {
        return static::$facade->getUrlGenerator();
    }


    public static function getStore() : RouterStore
    {
        return static::$facade->getStore();
    }


    public static function cacheClear() : RouterInterface
    {
        return static::$facade->cacheClear();
    }

    /**
     * @param callable $fn
     */
    public static function cacheRemember($fn, ?bool $commit = null) : RouterInterface
    {
        return static::$facade->cacheRemember($fn, $commit);
    }


    public static function newBlueprint(?RouteBlueprint $from = null) : RouteBlueprint
    {
        return static::$facade->newBlueprint($from);
    }

    /**
     * @param string|null                                    $path
     * @param string|string[]|null                           $httpMethods
     * @param callable|object|array|class-string|string|null $action
     *
     * @param string|null                                    $name
     * @param string|string[]|null                           $tags
     */
    public static function blueprint(
        ?RouteBlueprint $from = null,
        $path = null, $httpMethods = null, $action = null, $name = null, $tags = null
    ) : RouteBlueprint
    {
        return static::$facade->blueprint(
            $from,
            $path, $httpMethods, $action, $name, $tags
        );
    }


    public static function group(?RouteBlueprint $from = null) : RouteGroup
    {
        return static::$facade->group($from);
    }

    /**
     * @return int[]
     */
    public static function registerRouteGroup(RouteGroup $routeGroup) : array
    {
        return static::$facade->registerRouteGroup($routeGroup);
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

    public static function registerRoute(Route $route) : int
    {
        return static::$facade->registerRoute($route);
    }


    /**
     * @param string|RouterPattern $pattern
     * @param string|null          $regex
     */
    public static function pattern($pattern, $regex = null) : RouterInterface
    {
        return static::$facade->pattern($pattern, $regex);
    }

    public static function registerPattern(RouterPattern $pattern) : string
    {
        return static::$facade->registerPattern($pattern);
    }


    /**
     * @param callable|object|array|class-string|string $middleware
     */
    public static function middlewareOnRouteId($routeId, $middleware) : RouterInterface
    {
        return static::$facade->middlewareOnRouteId($routeId, $middleware);
    }

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $middleware
     */
    public static function middlewareOnRoutePath($routePath, $middleware) : RouterInterface
    {
        return static::$facade->middlewareOnRoutePath($routePath, $middleware);
    }

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $middleware
     */
    public static function middlewareOnRouteTag($routeTag, $middleware) : RouterInterface
    {
        return static::$facade->middlewareOnRouteTag($routeTag, $middleware);
    }

    public static function registerMiddleware(RouterGenericHandlerMiddleware $middleware) : int
    {
        return static::$facade->registerMiddleware($middleware);
    }


    /**
     * @param int                                       $routeId
     * @param callable|object|array|class-string|string $fallback
     */
    public static function fallbackOnRouteId($routeId, $fallback) : RouterInterface
    {
        return static::$facade->fallbackOnRouteId($routeId, $fallback);
    }

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $fallback
     */
    public static function fallbackOnRoutePath($routePath, $fallback) : RouterInterface
    {
        return static::$facade->fallbackOnRoutePath($routePath, $fallback);
    }

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRouteTag($routeTag, $fallback) : RouterInterface
    {
        return static::$facade->fallbackOnRouteTag($routeTag, $fallback);
    }

    public static function registerFallback(RouterGenericHandlerFallback $fallback) : int
    {
        return static::$facade->registerFallback($fallback);
    }



    public static function commit() : RouterInterface
    {
        return static::$facade->commit();
    }



    /**
     * @param int[] $routeIds
     *
     * @return Route[]
     */
    public static function matchAllByIds(array $routeIds) : array
    {
        return static::$facade->matchAllByIds($routeIds);
    }

    /**
     * @param int[] $routeIds
     */
    public static function matchFirstByIds($routeIds) : ?Route
    {
        return static::$facade->matchFirstByIds($routeIds);
    }


    /**
     * @param (string|RouteName)[] $routeNames
     *
     * @return Route[]|Route[][]
     */
    public static function matchAllByNames($routeNames, ?bool $unique = null) : array
    {
        return static::$facade->matchAllByNames($routeNames, $unique);
    }

    /**
     * @param (string|RouteName)[] $routeNames
     */
    public static function matchFirstByNames($routeNames) : ?Route
    {
        return static::$facade->matchFirstByNames($routeNames);
    }


    /**
     * @param (string|RouteTag)[] $routeTags
     *
     * @return Route[]|Route[][]
     */
    public static function matchAllByTags(array $routeTags, ?bool $unique = null) : array
    {
        return static::$facade->matchAllByTags($routeTags, $unique);
    }

    /**
     * @param (string|RouteTag)[] $routeTags
     */
    public static function matchFirstByTags($routeTags) : ?Route
    {
        return static::$facade->matchFirstByTags($routeTags);
    }


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
    public static function matchAllByParams(array $paramsList, ?bool $unique = null) : array
    {
        return static::$facade->matchAllByParams($paramsList, $unique);
    }

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
    public static function matchFirstByParams(array $paramsList) : ?Route
    {
        return static::$facade->matchFirstByParams($paramsList);
    }


    /**
     * @return Route[]
     */
    public static function matchByContract(RouterMatcherContractInterface $contract) : array
    {
        return static::$facade->matchByContract($contract);
    }

    public static function matchFirstByContract(RouterMatcherContractInterface $contract) : ?Route
    {
        return static::$facade->matchFirstByContract($contract);
    }



    /**
     * @param mixed|RouterDispatcherRequestContractInterface|RouterDispatcherRouteContractInterface $contract
     * @param array{ 0: array }|PipeContext                                                         $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public static function dispatch(
        $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        return static::$facade->dispatch(
            $contract,
            $input, $context, $args
        );
    }

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public static function dispatchByRequest(
        RouterDispatcherRequestContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        return static::$facade->dispatchByRequest(
            $contract,
            $input, $context, $args
        );
    }

    /**
     * @param array{ 0: array }|PipeContext $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public static function dispatchByRoute(
        RouterDispatcherRouteContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        return static::$facade->dispatchByRoute(
            $contract,
            $input, $context, $args
        );
    }


    public static function hasRequestContract(?RouterDispatcherRequestContractInterface &$contract = null) : bool
    {
        return static::$facade->hasRequestContract($contract);
    }

    public static function getRequestContract() : RouterDispatcherRequestContractInterface
    {
        return static::$facade->getRequestContract();
    }


    public static function hasRouteContract(?RouterDispatcherRouteContractInterface &$contract = null) : bool
    {
        return static::$facade->hasRouteContract($contract);
    }

    public static function getRouteContract() : RouterDispatcherRouteContractInterface
    {
        return static::$facade->getRouteContract();
    }


    public static function getDispatchRequestMethod() : string
    {
        return static::$facade->getDispatchRequestMethod();
    }

    public static function getDispatchRequestUri() : string
    {
        return static::$facade->getDispatchRequestUri();
    }

    public static function getDispatchRequestPath() : string
    {
        return static::$facade->getDispatchRequestPath();
    }


    public static function getDispatchRoute() : Route
    {
        return static::$facade->getDispatchRoute();
    }

    public static function getDispatchActionAttributes() : array
    {
        return static::$facade->getDispatchActionAttributes();
    }


    /**
     * @return RouterGenericHandlerMiddleware[]
     */
    public function getDispatchMiddlewareIndex() : array
    {
        return static::$facade->getDispatchMiddlewareIndex();
    }

    /**
     * @return RouterGenericHandlerFallback[]
     */
    public function getDispatchFallbackIndex() : array
    {
        return static::$facade->getDispatchFallbackIndex();
    }



    /**
     * @param (string|Route)[] $routes
     *
     * @return string[]
     */
    public static function urls(array $routes, array $attributes = []) : array
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


    public static function setFacade(?RouterInterface $facade) : ?RouterInterface
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
