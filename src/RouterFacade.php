<?php

namespace Gzhegow\Router;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Store\RouterStore;
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
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherInterface;
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
     * @var RouterStore
     */
    protected $routerStore;

    /**
     * @var RouteGroup
     */
    protected $rootRouterGroup;

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
        $this->config = $config;
        $this->config->validate();

        $this->routerFactory = $routerFactory;

        $this->routerCache = $routerCache;
        $this->routerDispatcher = $routerDispatcher;
        $this->routerInvoker = $routerInvoker;
        $this->routerMatcher = $routerMatcher;
        $this->routerUrlGenerator = $routerUrlGenerator;

        $this->routerStore = $this->routerFactory->newRouterStore();

        $this->rootRouterGroup = $this->routerFactory->newRouteGroup();

        $this->initialize();
    }

    protected function initialize() : void
    {
        $this->routerDispatcher->initialize($this);
        $this->routerMatcher->initialize($this);
        $this->routerUrlGenerator->initialize($this);
    }


    public function getConfig() : RouterConfig
    {
        return $this->config;
    }


    public function getFactory() : RouterFactoryInterface
    {
        return $this->routerFactory;
    }


    public function getCache() : RouterCacheInterface
    {
        return $this->routerCache;
    }

    public function getDispatcher() : RouterDispatcherInterface
    {
        return $this->routerDispatcher;
    }

    public function getInvoker() : RouterInvokerInterface
    {
        return $this->routerInvoker;
    }

    public function getMatcher() : RouterMatcherInterface
    {
        return $this->routerMatcher;
    }

    public function getUrlGenerator() : RouterUrlGeneratorInterface
    {
        return $this->routerUrlGenerator;
    }


    public function getStore() : RouterStore
    {
        return $this->routerStore;
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

        if ($commit) {
            $this->commit();
        }

        if ($this->isRouterChanged) {
            $this->cacheSave();
        }

        return $this;
    }

    protected function cacheLoad() : bool
    {
        if ($this->isRouterChanged) {
            throw new RuntimeException(
                [ 'You registered the new data before your cache was loaded. Please, call ->cacheSave() first' ]
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
            //
            'rootRouterNode'       => true,
        ];

        foreach ( $keys as $key => $bool ) {
            if (isset($cacheData[ $key ])) {
                $this->routerStore->{$key} = $cacheData[ $key ];
            }
        }

        return true;
    }

    protected function cacheSave() : RouterInterface
    {
        if (! $this->isRouterChanged) {
            return $this;
        }

        if ($this->config->registerAllowObjectsAndClosures) {
            return $this;
        }

        $cacheData = [
            'routeCollection'      => $this->routerStore->routeCollection,
            'middlewareCollection' => $this->routerStore->middlewareCollection,
            'fallbackCollection'   => $this->routerStore->fallbackCollection,
            'patternCollection'    => $this->routerStore->patternCollection,
            //
            'rootRouterNode'       => $this->routerStore->rootRouterNode,
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

        $id = $this->routerStore->routeCollection->registerRoute($route);

        $path = $route->path;

        $split = $path;
        $split = ltrim($split, '/');
        $split = explode('/', $split);
        while ( [] !== $split ) {
            $routeNodePrevious = $routeNodePrevious ?? $this->routerStore->rootRouterNode;

            $part = array_shift($split);
            $partRegex = null;

            $isPattern = (false !== strpos($part, Router::PATTERN_ENCLOSURE[ 0 ]));
            $isLast = ([] === $split);

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
                    $routeNode = $routeNodePrevious->childNodeListByRegex[ $partRegex ] ?? null;

                    if (null === $routeNode) {
                        $routeNode = $this->routerFactory->newRouterNode();
                        $routeNode->part = $part;

                        $routeNodePrevious->childNodeListByRegex[ $partRegex ] = $routeNode;
                    }

                } else {
                    $routeNode = $routeNodePrevious->childNodeListByPart[ $part ] ?? null;

                    if (null === $routeNode) {
                        $routeNode = $this->routerFactory->newRouterNode();
                        $routeNode->part = $part;

                        $routeNodePrevious->childNodeListByPart[ $part ] = $routeNode;
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

        $routePatternObject = RouterPattern::from($routePattern)->orThrow();

        $this->registerPattern($routePatternObject);

        return $this;
    }

    public function registerPattern(RouterPattern $pattern) : string
    {
        $this->isRouterChanged = true;

        $id = $this->routerStore->patternCollection->registerPattern($pattern);

        return $id;
    }


    /**
     * @param int                                       $routeId
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRouteId($routeId, $middleware) : RouterInterface
    {
        $theType = Lib::type();

        $routeIdInt = $theType->int_positive($routeId)->orThrow();

        $routerGenericMiddleware = RouterGenericHandlerMiddleware::from($middleware)->orThrow();

        if (! $this->routerStore->routeCollection->hasRoute($routeIdInt)) {
            throw new RuntimeException(
                [ 'Route not found by id: ' . $routeId, $routeId ]
            );
        }

        $this->registerMiddleware($routerGenericMiddleware);

        $this->routerStore->middlewareCollection->addRouteIdMiddleware($routeIdInt, $routerGenericMiddleware);

        return $this;
    }

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRoutePath($routePath, $middleware) : RouterInterface
    {
        $routePathObject = RoutePath::from($routePath)->orThrow();

        $routerGenericMiddleware = RouterGenericHandlerMiddleware::from($middleware)->orThrow();

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

        $this->registerMiddleware($routerGenericMiddleware);

        $this->routerStore->middlewareCollection->addRoutePathMiddleware($routePathObject, $routerGenericMiddleware);

        return $this;
    }

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnRouteTag($routeTag, $middleware) : RouterInterface
    {
        $routeTagObject = RouteTag::from($routeTag)->orThrow();

        $routerGenericMiddleware = RouterGenericHandlerMiddleware::from($middleware)->orThrow();

        $this->registerMiddleware($routerGenericMiddleware);

        $this->routerStore->middlewareCollection->addRouteTagMiddleware($routeTagObject, $routerGenericMiddleware);

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

        $id = $this->routerStore->middlewareCollection->registerMiddleware($middleware);

        return $id;
    }


    /**
     * @param int                                       $routeId
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRouteId($routeId, $fallback) : RouterInterface
    {
        $theType = Lib::type();

        $routeIdInt = $theType->int_positive($routeId)->orThrow();

        $routerGenericFallback = RouterGenericHandlerFallback::from($fallback)->orThrow();

        if (! $this->routerStore->routeCollection->hasRoute($routeIdInt)) {
            throw new RuntimeException(
                [ 'Route not found by id: ' . $routeId, $routeId ]
            );
        }

        $this->registerFallback($routerGenericFallback);

        $this->routerStore->fallbackCollection->addRouteIdFallback($routeIdInt, $routerGenericFallback);

        return $this;
    }

    /**
     * @param string|RoutePath                          $routePath
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRoutePath($routePath, $fallback) : RouterInterface
    {
        $routePathObject = RoutePath::from($routePath)->orThrow();

        $routerGenericFallback = RouterGenericHandlerFallback::from($fallback)->orThrow();

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

        $this->registerFallback($routerGenericFallback);

        $this->routerStore->fallbackCollection->addRoutePathFallback($routePathObject, $routerGenericFallback);

        return $this;
    }

    /**
     * @param string|RouteTag                           $routeTag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnRouteTag($routeTag, $fallback) : RouterInterface
    {
        $routeTagObject = RouteTag::from($routeTag)->orThrow();

        $routerGenericFallback = RouterGenericHandlerFallback::from($fallback)->orThrow();

        $this->registerFallback($routerGenericFallback);

        $this->routerStore->fallbackCollection->addRouteTagFallback($routeTagObject, $routerGenericFallback);

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

        $id = $this->routerStore->fallbackCollection->registerFallback($fallback);

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
     * @param int[] $idList
     *
     * @return Route[]
     */
    public function matchAllByIds(array $idList) : array
    {
        return $this->routerMatcher->matchAllByIds($idList);
    }

    /**
     * @param int[] $idList
     */
    public function matchFirstByIds(array $idList) : ?Route
    {
        return $this->routerMatcher->matchFirstByIds($idList);
    }


    /**
     * @param (string|RouteName)[] $nameList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $nameList, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByNames($nameList, $unique);
    }

    /**
     * @param (string|RouteName)[] $nameList
     */
    public function matchFirstByNames(array $nameList) : ?Route
    {
        return $this->routerMatcher->matchFirstByNames($nameList);
    }


    /**
     * @param (string|RouteTag)[] $tagList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $tagList, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByTags($tagList, $unique);
    }

    /**
     * @param (string|RouteTag)[] $tagList
     */
    public function matchFirstByTags(array $tagList) : ?Route
    {
        return $this->routerMatcher->matchFirstByTags($tagList);
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
    public function matchAllByParams(array $paramsList, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByParams($paramsList, $unique);
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
    public function matchFirstByParams(array $paramsList) : ?Route
    {
        return $this->routerMatcher->matchFirstByParams($paramsList);
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

        $theType = Lib::type();

        $patternDict = $this->routerStore->patternCollection->patternDict;

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
                        [ 'The `path` should not contain same attribute few times: ' . $attribute, $attribute ]
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

        $theType->regex('/^' . $pathRegex . '$/')->orThrow();

        $attributesIndex = $attributesIndex ?? [];

        return $pathRegex;
    }
}
