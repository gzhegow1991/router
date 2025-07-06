<?php

namespace Gzhegow\Router;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Lib\Modules\Func\Pipe\PipeContext;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Cache\RouterCacheInterface;
use Gzhegow\Router\Core\Matcher\RouterMatcherInterface;
use Gzhegow\Router\Core\Invoker\RouterInvokerInterface;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Collection\RouterPatternCollection;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherInterface;
use Gzhegow\Router\Core\Collection\RouterMiddlewareCollection;
use Gzhegow\Router\Core\UrlGenerator\RouterUrlGeneratorInterface;
use Gzhegow\Router\Core\Handler\Fallback\RouterGenericHandlerFallback;
use Gzhegow\Router\Core\Matcher\Contract\RouterMatcherContractInterface;
use Gzhegow\Router\Core\Handler\Middleware\RouterGenericHandlerMiddleware;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRouteContractInterface;
use Gzhegow\Router\Core\Dispatcher\Contract\RouterDispatcherRequestContractInterface;


class RouterFacade implements RouterInterface
{
    /**
     * @var RouterFactoryInterface
     */
    protected $routerFactory;

    /**
     * @var RouterConfig
     */
    protected $config;

    /**
     * @var RouterCacheInterface
     */
    protected $routerCache;
    /**
     * @var RouterDispatcherInterface
     */
    protected $routerDispatcher;
    /**
     * @var RouterInvokerInterface
     */
    protected $routerInvoker;
    /**
     * @var RouterMatcherInterface
     */
    protected $routerMatcher;
    /**
     * @var RouterUrlGeneratorInterface
     */
    protected $routerUrlGenerator;

    /**
     * @var RouterRouteCollection
     */
    protected $routeCollection;
    /**
     * @var RouterPatternCollection
     */
    protected $patternCollection;
    /**
     * @var RouterMiddlewareCollection
     */
    protected $middlewareCollection;
    /**
     * @var RouterFallbackCollection
     */
    protected $fallbackCollection;

    /**
     * @var RouteGroup
     */
    protected $rootRouterGroup;
    /**
     * @var RouterNode
     */
    protected $rootRouterNode;

    /**
     * @var bool
     */
    protected $isRouterChanged = false;


    public function __construct(
        RouterFactoryInterface $routerFactory,
        //
        RouterCacheInterface $routerCache,
        RouterDispatcherInterface $routerDispatcher,
        RouterInvokerInterface $routerInvoker,
        RouterMatcherInterface $routerMatcher,
        RouterUrlGeneratorInterface $routerUrlGenerator,
        //
        RouterConfig $config
    )
    {
        $this->routerFactory = $routerFactory;

        $this->routerCache = $routerCache;
        $this->routerDispatcher = $routerDispatcher;
        $this->routerInvoker = $routerInvoker;
        $this->routerMatcher = $routerMatcher;
        $this->routerUrlGenerator = $routerUrlGenerator;

        $this->routeCollection = $this->routerFactory->newRouteCollection();
        $this->patternCollection = $this->routerFactory->newPatternCollection();
        $this->middlewareCollection = $this->routerFactory->newMiddlewareCollection();
        $this->fallbackCollection = $this->routerFactory->newFallbackCollection();

        $this->config = $config;
        $this->config->validate();

        $this->rootRouterGroup = $this->routerFactory->newRouteGroup();

        $routerNodeRoot = $this->routerFactory->newRouterNode();
        $routerNodeRoot->part = '';
        $this->rootRouterNode = $routerNodeRoot;

        $this->initialize();
    }

    protected function initialize() : void
    {
        $this->routerDispatcher->initialize($this);
        $this->routerMatcher->initialize($this);
        $this->routerUrlGenerator->initialize($this);
    }


    public function getRouterFactory() : RouterFactoryInterface
    {
        return $this->routerFactory;
    }


    public function getRouterCache() : RouterCacheInterface
    {
        return $this->routerCache;
    }

    public function getRouterDispatcher() : RouterDispatcherInterface
    {
        return $this->routerDispatcher;
    }

    public function getRouterInvoker() : RouterInvokerInterface
    {
        return $this->routerInvoker;
    }

    public function getRouterMatcher() : RouterMatcherInterface
    {
        return $this->routerMatcher;
    }

    public function getRouterUrlGenerator() : RouterUrlGeneratorInterface
    {
        return $this->routerUrlGenerator;
    }


    public function getRouteCollection() : RouterRouteCollection
    {
        return $this->routeCollection;
    }

    public function getPatternCollection() : RouterPatternCollection
    {
        return $this->patternCollection;
    }

    public function getMiddlewareCollection() : RouterMiddlewareCollection
    {
        return $this->middlewareCollection;
    }

    public function getFallbackCollection() : RouterFallbackCollection
    {
        return $this->fallbackCollection;
    }


    public function getRootRouterGroup() : RouteGroup
    {
        return $this->rootRouterGroup;
    }

    public function getRootRouterNode() : RouterNode
    {
        return $this->rootRouterNode;
    }


    public function getConfig() : RouterConfig
    {
        return $this->config;
    }


    public function cacheClear() : RouterInterface
    {
        $this->routerCache->clearCache();

        return $this;
    }

    /**
     * @param callable $fn
     */
    public function cacheRemember($fn, ?bool $commit = null) : RouterInterface
    {
        if ($this->cacheLoad()) {
            return $this;
        }

        $commit = $commit ?? true;

        $fn($this);

        if ($this->isRouterChanged) {
            $this->cacheSave();
        }

        if ($commit) {
            $this->commit();
        }

        return $this;
    }

    protected function cacheLoad() : bool
    {
        if ($this->isRouterChanged) {
            throw new RuntimeException(
                'You have registered new data before your cache is loaded'
            );
        }

        $cacheData = $this->routerCache->loadCache();
        if (null === $cacheData) {
            return false;
        }

        $keys = [
            'routeCollection'      => true,
            'middlewareCollection' => true,
            'fallbackCollection'   => true,
            'patternCollection'    => true,
            'routerNodeRoot'       => true,
        ];

        foreach ( $keys as $key => $bool ) {
            if (isset($cacheData[ $key ])) {
                $this->{$key} = $cacheData[ $key ];
            }
        }

        return true;
    }

    protected function cacheSave() : RouterInterface
    {
        if (! $this->isRouterChanged) return $this;
        if ($this->config->registerAllowObjectsAndClosures) return $this;

        $cacheData = [
            'routeCollection'      => $this->routeCollection,
            'middlewareCollection' => $this->middlewareCollection,
            'fallbackCollection'   => $this->fallbackCollection,
            'patternCollection'    => $this->patternCollection,
            //
            'routerNodeRoot'       => $this->rootRouterNode,
        ];

        $this->routerCache->saveCache($cacheData);

        $this->isRouterChanged = false;

        return $this;
    }


    public function newBlueprint(?RouteBlueprint $from = null) : RouteBlueprint
    {
        $routeBlueprint = $this->rootRouterGroup->newBlueprint($from);

        return $routeBlueprint;
    }

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
    ) : RouteBlueprint
    {
        $routeBlueprint = $this->rootRouterGroup->blueprint(
            $from,
            $path, $httpMethods, $action, $name, $tags
        );

        return $routeBlueprint;
    }



    public function group(?RouteBlueprint $from = null) : RouteGroup
    {
        $routeGroup = $this->rootRouterGroup->group($from);

        return $routeGroup;
    }

    /**
     * @return int[]
     */
    public function registerRouteGroup(RouteGroup $routeGroup) : array
    {
        $report = [];

        foreach ( $routeGroup->getRoutes() as $routeBlueprint ) {
            $route = $this->compileRoute($routeBlueprint);

            $routeId = $this->registerRoute($route);

            if ($routeBlueprint->middlewareDict) {
                foreach ( $routeBlueprint->middlewareDict as $middleware ) {
                    $this->middlewareOnRouteId($routeId, $middleware);
                }
            }

            if ($routeBlueprint->fallbackDict) {
                foreach ( $routeBlueprint->fallbackDict as $fallback ) {
                    $this->fallbackOnRouteId($routeId, $fallback);
                }
            }

            $report[ $routeId ] = $route;
        }

        return $report;
    }


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
    ) : RouteBlueprint
    {
        $routeGroup = $this->rootRouterGroup->route(
            $path, $httpMethods, $action,
            $name, $tags
        );

        return $routeGroup;
    }

    public function addRoute(RouteBlueprint $routeBlueprint) : RouterInterface
    {
        $this->rootRouterGroup->addRoute($routeBlueprint);

        return $this;
    }

    public function registerRoute(Route $route) : int
    {
        $this->isRouterChanged = true;

        if (! $this->config->registerAllowObjectsAndClosures) {
            $isRuntimeAction = null
                ?? $route->action->hasClosureObject()
                ?? $route->action->hasMethodObject()
                ?? $route->action->hasInvokableObject();

            if ($isRuntimeAction) {
                throw new RuntimeException(
                    [
                        'The `action` should not be runtime object or \Closure',
                        $route->action,
                        $route,
                    ]
                );
            }
        }

        $id = $this->routeCollection->registerRoute($route);

        $path = $route->path;

        $slice = $path;
        $slice = ltrim($slice, '/');
        $slice = explode('/', $slice);
        while ( $slice ) {
            $routeNodePrevious = $routeNodePrevious ?? $this->rootRouterNode;

            $part = array_shift($slice);
            $partRegex = null;

            $isPattern = (false !== strpos($part, Router::PATTERN_ENCLOSURE[ 0 ]));
            $isLast = ([] === $slice);

            if ($isPattern) {
                $partRegex = $this->compilePathRegex($part);
            }

            if ($isLast) {
                if ($isPattern) {
                    $routeNodePrevious->routeIndexByRegex[ $partRegex ][ $route->id ] = true;

                } else {
                    $routeNodePrevious->routeIndexByPart[ $part ][ $route->id ] = true;
                }

                foreach ( $route->methodIndex as $httpMethod => $bool ) {
                    $routeNodePrevious->routeIndexByMethod[ $httpMethod ][ $route->id ] = true;
                }

            } else {
                if ($isPattern) {
                    $routeNode = $routeNodePrevious->childrenByRegex[ $partRegex ] ?? null;

                    if (null === $routeNode) {
                        $routeNode = $this->routerFactory->newRouterNode();
                        $routeNode->part = $part;

                        $routeNodePrevious->childrenByRegex[ $partRegex ] = $routeNode;
                    }

                } else {
                    $routeNode = $routeNodePrevious->childrenByPart[ $part ] ?? null;

                    if (null === $routeNode) {
                        $routeNode = $this->routerFactory->newRouterNode();
                        $routeNode->part = $part;

                        $routeNodePrevious->childrenByPart[ $part ] = $routeNode;
                    }
                }

                $routeNodePrevious = $routeNode;
            }
        }

        return $id;
    }


    /**
     * @param string|RouterPattern $pattern
     * @param string|null          $regex
     */
    public function pattern($pattern, $regex = null) : RouterInterface
    {
        $routePattern = (null === $regex)
            ? $pattern
            : [ $pattern, $regex ];

        $routePatternObject = RouterPattern::from($routePattern);

        $this->registerPattern($routePatternObject);

        return $this;
    }

    public function registerPattern(RouterPattern $pattern) : string
    {
        $this->isRouterChanged = true;

        $id = $this->patternCollection->registerPattern($pattern);

        return $id;
    }


    /**
     * @param int                                       $routeId
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRouteId($routeId, $middleware) : RouterInterface
    {
        $routeIdInt = Lib::parseThrow()->int_positive($routeId);
        $middlewareObject = RouterGenericHandlerMiddleware::from($middleware);

        if (! $this->routeCollection->hasRoute($routeIdInt)) {
            throw new RuntimeException(
                [ 'Route not found by id: ' . $routeId, $routeId ]
            );
        }

        $this->registerMiddleware($middlewareObject);

        $this->middlewareCollection->addRouteIdMiddleware($routeIdInt, $middlewareObject);

        return $this;
    }

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRoutePath($routePath, $middleware) : RouterInterface
    {
        $routePathObject = RoutePath::from($routePath);
        $middlewareObject = RouterGenericHandlerMiddleware::from($middleware);

        if ($this->config->compileTrailingSlashMode) {
            $routePathString = $routePathObject->getValue();

            $isEndsWithSlash = true
                && ('/' !== $routePathString)
                && ('/' === $routePathString[ strlen($routePathString) - 1 ]);

            if ($isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_NEVER)) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $routePathString
                );

            } elseif (! $isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS)) {
                throw new RuntimeException(
                    'The `path` must end with `/` sign: ' . $routePathString
                );
            }
        }

        $this->registerMiddleware($middlewareObject);

        $this->middlewareCollection->addRoutePathMiddleware($routePathObject, $middlewareObject);

        return $this;
    }

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRouteTag($routeTag, $middleware) : RouterInterface
    {
        $routeTagObject = RouteTag::from($routeTag);
        $middlewareObject = RouterGenericHandlerMiddleware::from($middleware);

        $this->registerMiddleware($middlewareObject);

        $this->middlewareCollection->addRouteTagMiddleware($routeTagObject, $middlewareObject);

        return $this;
    }

    public function registerMiddleware(RouterGenericHandlerMiddleware $middleware) : int
    {
        $this->isRouterChanged = true;

        if (! $this->config->registerAllowObjectsAndClosures) {
            if (false
                || $middleware->isClosure()
                || $middleware->hasMethodObject()
                || $middleware->hasInvokableObject()
            ) {
                throw new RuntimeException(
                    [
                        'This `middleware` should not be runtime object or \Closure',
                        $middleware,
                    ]
                );
            }
        }

        $id = $this->middlewareCollection->registerMiddleware($middleware);

        return $id;
    }


    /**
     * @param int                                       $routeId
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRouteId($routeId, $fallback) : RouterInterface
    {
        $routeIdInt = Lib::parseThrow()->int_positive($routeId);
        $fallbackObject = RouterGenericHandlerFallback::from($fallback);

        if (! $this->routeCollection->hasRoute($routeIdInt)) {
            throw new RuntimeException(
                [ 'Route not found by id: ' . $routeId, $routeId ]
            );
        }

        $this->registerFallback($fallbackObject);

        $this->fallbackCollection->addRouteIdFallback($routeIdInt, $fallbackObject);

        return $this;
    }

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRoutePath($routePath, $fallback) : RouterInterface
    {
        $routePathObject = RoutePath::from($routePath);
        $fallbackObject = RouterGenericHandlerFallback::from($fallback);

        if ($this->config->compileTrailingSlashMode) {
            $routePathString = $routePathObject->getValue();

            $isEndsWithSlash = true
                && ('/' !== $routePathString)
                && ('/' === $routePathString[ strlen($routePathString) - 1 ]);

            if ($isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_NEVER)) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $routePathString
                );

            } elseif (! $isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS)) {
                throw new RuntimeException(
                    'The `path` must end with `/` sign: ' . $routePathString
                );
            }
        }

        $this->registerFallback($fallbackObject);

        $this->fallbackCollection->addRoutePathFallback($routePathObject, $fallbackObject);

        return $this;
    }

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRouteTag($routeTag, $fallback) : RouterInterface
    {
        $routeTagObject = RouteTag::from($routeTag);
        $fallbackObject = RouterGenericHandlerFallback::from($fallback);

        $this->registerFallback($fallbackObject);

        $this->fallbackCollection->addRouteTagFallback($routeTagObject, $fallbackObject);

        return $this;
    }

    public function registerFallback(RouterGenericHandlerFallback $fallback) : int
    {
        $this->isRouterChanged = true;

        if (! $this->config->registerAllowObjectsAndClosures) {
            if (false
                || $fallback->isClosure()
                || $fallback->hasMethodObject()
                || $fallback->hasInvokableObject()
            ) {
                throw new RuntimeException(
                    [
                        'This `fallback` should not be runtime object or \Closure',
                        $fallback,
                    ]
                );
            }
        }

        $id = $this->fallbackCollection->registerFallback($fallback);

        return $id;
    }


    public function commit() : RouterInterface
    {
        if ($this->rootRouterGroup->hasRoutes()) {
            $this->registerRouteGroup($this->rootRouterGroup);

            $this->rootRouterGroup = $this->routerFactory->newRouteGroup();
        }

        return $this;
    }



    /**
     * @param int[] $routeIds
     *
     * @return Route[]
     */
    public function matchAllByIds(array $routeIds) : array
    {
        return $this->routerMatcher->matchAllByIds($routeIds);
    }

    /**
     * @param int[] $routeIds
     */
    public function matchFirstByIds(array $routeIds) : ?Route
    {
        return $this->routerMatcher->matchFirstByIds($routeIds);
    }


    /**
     * @param (string|RouteName)[] $routeNames
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $routeNames, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByNames($routeNames, $unique);
    }

    /**
     * @param (string|RouteName)[] $routeNames
     */
    public function matchFirstByNames(array $routeNames) : ?Route
    {
        return $this->routerMatcher->matchFirstByNames($routeNames);
    }


    /**
     * @param (string|RouteTag)[] $routeTags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $routeTags, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByTags($routeTags, $unique);
    }

    /**
     * @param (string|RouteTag)[] $routeTags
     */
    public function matchFirstByTags(array $routeTags) : ?Route
    {
        return $this->routerMatcher->matchFirstByTags($routeTags);
    }


    /**
     * @param array{
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     * }[] $routeNameTagMethods
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNameTagMethods(array $routeNameTagMethods, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByNameTagMethods($routeNameTagMethods, $unique);
    }

    /**
     * @param array{
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     * }[] $routeNameTagMethods
     */
    public function matchFirstByNameTagMethods(array $routeNameTagMethods) : ?Route
    {
        return $this->routerMatcher->matchFirstByNameTagMethods($routeNameTagMethods);
    }


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContractInterface $contract) : array
    {
        return $this->routerMatcher->matchByContract($contract);
    }

    public function matchFirstByContract(RouterMatcherContractInterface $contract) : ?Route
    {
        return $this->routerMatcher->matchFirstByContract($contract);
    }



    /**
     * @param mixed|RouterDispatcherRequestContractInterface|RouterDispatcherRouteContractInterface $contract
     * @param array{ 0: array }|PipeContext                                                         $context
     *
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        return $this->routerDispatcher->dispatch(
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
    public function dispatchByRequest(
        RouterDispatcherRequestContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        return $this->routerDispatcher->dispatchByRequest(
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
    public function dispatchByRoute(
        RouterDispatcherRouteContractInterface $contract,
        $input = null,
        $context = null,
        array $args = []
    )
    {
        return $this->routerDispatcher->dispatchByRoute(
            $contract,
            $input, $context, $args
        );
    }


    public function hasRequestContract(?RouterDispatcherRequestContractInterface &$contract = null) : bool
    {
        return $this->routerDispatcher->hasRequestContract($contract);
    }

    public function getRequestContract() : RouterDispatcherRequestContractInterface
    {
        return $this->routerDispatcher->getRequestContract();
    }


    public function hasRouteContract(?RouterDispatcherRouteContractInterface &$contract = null) : bool
    {
        return $this->routerDispatcher->hasRouteContract($contract);
    }

    public function getRouteContract() : RouterDispatcherRouteContractInterface
    {
        return $this->routerDispatcher->getRouteContract();
    }


    public function getDispatchRequestMethod() : string
    {
        return $this->routerDispatcher->getDispatchRequestMethod();
    }

    public function getDispatchRequestUri() : string
    {
        return $this->routerDispatcher->getDispatchRequestUri();
    }

    public function getDispatchRequestPath() : string
    {
        return $this->routerDispatcher->getDispatchRequestPath();
    }


    public function getDispatchRoute() : Route
    {
        return $this->routerDispatcher->getDispatchRoute();
    }

    public function getDispatchActionAttributes() : array
    {
        return $this->routerDispatcher->getDispatchActionAttributes();
    }


    /**
     * @return RouterGenericHandlerMiddleware[]
     */
    public function getDispatchMiddlewareIndex() : array
    {
        return $this->routerDispatcher->getDispatchMiddlewareIndex();
    }

    /**
     * @return RouterGenericHandlerFallback[]
     */
    public function getDispatchFallbackIndex() : array
    {
        return $this->routerDispatcher->getDispatchFallbackIndex();
    }


    /**
     * @param (string|Route)[] $routes
     *
     * @return string[]
     */
    public function urls(array $routes, array $attributes = []) : array
    {
        return $this->routerUrlGenerator->urls($routes, $attributes);
    }

    /**
     * @param Route|string $route
     */
    public function url($route, array $attributes = []) : string
    {
        return $this->routerUrlGenerator->url($route, $attributes);
    }


    protected function compileRoute(RouteBlueprint $routeBlueprint) : Route
    {
        if (null === ($routePath = $routeBlueprint->path)) {
            throw new RuntimeException(
                [
                    'Missing `path` in route',
                    $routeBlueprint,
                ]
            );
        }

        if (null === $routeBlueprint->action) {
            throw new RuntimeException(
                [
                    'Missing `action` in route',
                    $routeBlueprint,
                ]
            );
        }

        if (null === $routeBlueprint->methodIndex) {
            throw new RuntimeException(
                [
                    'Missing `method` in route',
                    $routeBlueprint,
                ]
            );
        }

        $routePathString = $routePath->getValue();

        if ($this->config->compileTrailingSlashMode) {
            $isEndsWithSlash = true
                && ('/' !== $routePathString)
                && ('/' === $routePathString[ strlen($routePathString) - 1 ]);

            if ($isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_NEVER)) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $routePathString
                );

            } elseif (! $isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS)) {
                throw new RuntimeException(
                    'The `path` must end with `/` sign: ' . $routePathString
                );
            }
        }

        $pathRegex = $this->compilePathRegex($routePathString, $attributesIndex);

        $route = $this->routerFactory->newRoute();

        $route->path = $routePathString;
        $route->compiledPathRegex = $pathRegex;

        $route->action = $routeBlueprint->action;
        $route->compiledActionAttributes = array_fill_keys(array_keys($attributesIndex), null);

        if (null !== $routeBlueprint->name) {
            $route->name = $routeBlueprint->name->getValue();
        }

        if ([] !== $routeBlueprint->methodIndex) {
            $methodIndex = $routeBlueprint->methodIndex;

            ksort($methodIndex);

            $route->methodIndex = $methodIndex;
        }

        if ([] !== $routeBlueprint->tagIndex) {
            $tagIndex = $routeBlueprint->tagIndex;

            ksort($tagIndex);

            $route->tagIndex = $tagIndex;
        }

        return $route;
    }

    protected function compilePathRegex(string $path, ?array &$attributesIndex = null) : string
    {
        $attributesIndex = null;

        $patternDict = $this->patternCollection->patternDict;

        $regex = ''
            . preg_quote(Router::PATTERN_ENCLOSURE[ 0 ], '/')
            . '[^' . preg_quote(Router::PATTERN_ENCLOSURE[ 1 ], '/') . ']+'
            . preg_quote(Router::PATTERN_ENCLOSURE[ 1 ], '/');

        $search = [];

        $pathRegex = preg_replace_callback(
            '/' . $regex . '/',
            static function (array $match) use (
                &$patternDict, &$attributesIndex,
                &$search
            ) {
                $patternObject = $patternDict[ $match[ 0 ] ];

                $attribute = $patternObject->attribute;

                if (isset($attributesIndex[ $attribute ])) {
                    throw new RuntimeException(
                        'The `path` should not contain same attribute few times: ' . $attribute
                    );
                }

                $attributesIndex[ $attribute ] = null;

                $search[ preg_quote($patternObject->pattern, '/') ] = $patternObject->regex;

                return $patternObject->pattern;
            },
            $path
        );

        $pathRegex = preg_quote($pathRegex, '/');

        $pathRegex = str_replace(
            array_keys($search),
            array_values($search),
            $pathRegex
        );

        unset($patternDict);

        if (! Lib::type()->regex($r, '/^' . $pathRegex . '$/')) {
            throw new RuntimeException(
                'The output regex is not valid: ' . $pathRegex
            );
        }

        $attributesIndex = $attributesIndex ?? [];

        return $pathRegex;
    }
}
