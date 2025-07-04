<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
use Gzhegow\Router\Core\Route\Struct\RouteNameTag;
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


    public static function getRouterFactory() : RouterFactoryInterface
    {
        return static::$facade->getRouterFactory();
    }


    public static function getRouterCache() : RouterCacheInterface
    {
        return static::$facade->getRouterCache();
    }

    public static function getRouterDispatcher() : RouterDispatcherInterface
    {
        return static::$facade->getRouterDispatcher();
    }

    public static function getRouterInvoker() : RouterInvokerInterface
    {
        return static::$facade->getRouterInvoker();
    }

    public static function getRouterMatcher() : RouterMatcherInterface
    {
        return static::$facade->getRouterMatcher();
    }

    public static function getRouterUrlGenerator() : RouterUrlGeneratorInterface
    {
        return static::$facade->getRouterUrlGenerator();
    }


    public static function getRouteCollection() : RouterRouteCollection
    {
        return static::$facade->getRouteCollection();
    }

    public static function getPatternCollection() : RouterPatternCollection
    {
        return static::$facade->getPatternCollection();
    }

    public static function getMiddlewareCollection() : RouterMiddlewareCollection
    {
        return static::$facade->getMiddlewareCollection();
    }

    public static function getFallbackCollection() : RouterFallbackCollection
    {
        return static::$facade->getFallbackCollection();
    }


    public static function getRootRouterGroup() : RouteGroup
    {
        return static::$facade->getRootRouterGroup();
    }

    public static function getRootRouterNode() : RouterNode
    {
        return static::$facade->getRootRouterNode();
    }


    public static function getConfig() : RouterConfig
    {
        return static::$facade->getConfig();
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

    public static function registerMiddleware(GenericHandlerMiddleware $middleware) : int
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

    public static function registerFallback(GenericHandlerFallback $fallback) : int
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
     * @param (array{ 0: string, 1: string }|RouteNameTag)[] $routeNameTags
     *
     * @return Route[]|Route[][]
     */
    public static function matchAllByNameTags(array $routeNameTags, ?bool $unique = null) : array
    {
        return static::$facade->matchAllByNameTags($routeNameTags, $unique);
    }

    /**
     * @param (array{ 0: string, 1: string }|RouteNameTag)[] $routeNameTags
     */
    public static function matchFirstByNameTags(array $routeNameTags) : ?Route
    {
        return static::$facade->matchFirstByNameTags($routeNameTags);
    }


    /**
     * @return Route[]
     */
    public static function matchByContract(RouterMatcherContract $contract) : array
    {
        return static::$facade->matchByContract($contract);
    }



    /**
     * @param mixed|RouterDispatcherContract $contract
     * @param array{ 0: array }|PipeContext  $context
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


    public static function getDispatchContract() : RouterDispatcherContract
    {
        return static::$facade->getDispatchContract();
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
