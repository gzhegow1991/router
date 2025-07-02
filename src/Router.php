<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
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
    public static function cacheRemember($fn) : RouterInterface
    {
        return static::$facade->cacheRemember($fn);
    }


    public static function newBlueprint(?RouteBlueprint $from = null) : RouteBlueprint
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
     * @param string $pattern
     * @param string $regex
     */
    public static function pattern($pattern, $regex) : RouterInterface
    {
        return static::$facade->pattern($pattern, $regex);
    }

    public static function registerPattern(RouterPattern $pattern) : string
    {
        return static::$facade->registerPattern($pattern);
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

    public static function registerMiddleware(GenericHandlerMiddleware $middleware) : int
    {
        return static::$facade->registerMiddleware($middleware);
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

    public static function registerFallback(GenericHandlerFallback $fallback) : int
    {
        return static::$facade->registerFallback($fallback);
    }


    public static function commit() : RouterInterface
    {
        return static::$facade->commit();
    }


    /**
     * @return mixed
     * @throws DispatchException
     */
    public static function dispatch(
        RouterDispatcherContract $contract,
        $input = null,
        &$context = null,
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

    public static function getDispatchRoute() : Route
    {
        return static::$facade->getDispatchRoute();
    }

    public static function getDispatchActionAttributes() : array
    {
        return static::$facade->getDispatchActionAttributes();
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
    public static function matchAllByNames($names, ?bool $unique = null) : array
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
    public static function matchAllByTags($tags, ?bool $unique = null) : array
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
    public static function matchByContract(RouterMatcherContract $contract) : array
    {
        return static::$facade->matchByContract($contract);
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
