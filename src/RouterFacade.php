<?php

namespace Gzhegow\Router;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\Struct\Tag;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Core\Cache\RouterCacheInterface;
use Gzhegow\Router\Core\Matcher\RouterMatcherContract;
use Gzhegow\Router\Core\Matcher\RouterMatcherInterface;
use Gzhegow\Router\Core\Invoker\RouterInvokerInterface;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Collection\RouterPatternCollection;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherContract;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
use Gzhegow\Router\Core\Dispatcher\RouterDispatcherInterface;
use Gzhegow\Router\Core\Collection\RouterMiddlewareCollection;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Core\UrlGenerator\RouterUrlGeneratorInterface;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


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
        $routerNodeRoot->part = '/';
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
    public function cacheRemember($fn) : RouterInterface
    {
        if ($this->cacheLoad()) {
            return $this;
        }

        $fn($this);

        if ($this->isRouterChanged) {
            $this->cacheSave();
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

            $id = $this->registerRoute($route);

            $report[ $id ] = $route;
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
        $slice = trim($slice, '/');
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

                foreach ( $route->httpMethodIndex as $httpMethod => $bool ) {
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
     * @param string $pattern
     * @param string $regex
     */
    public function pattern($pattern, $regex) : RouterInterface
    {
        $pattern = RouterPattern::from([ $pattern, $regex ]);

        $this->registerPattern($pattern);

        return $this;
    }

    public function registerPattern(RouterPattern $pattern) : string
    {
        $this->isRouterChanged = true;

        $id = $this->patternCollection->registerPattern($pattern);

        return $id;
    }


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnPath($path, $middleware) : RouterInterface
    {
        $pathObject = Path::from($path);
        $middlewareObject = GenericHandlerMiddleware::from($middleware);

        if ($this->config->compileTrailingSlashMode) {
            $pathValue = $pathObject->getValue();

            $isEndsWithSlash = true
                && ('/' !== $pathValue)
                && ('/' === $pathValue[ strlen($pathValue) - 1 ]);

            if ($isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_NEVER)) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $pathValue
                );

            } elseif (! $isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS)) {
                throw new RuntimeException(
                    'The `path` must end with `/` sign: ' . $pathValue
                );
            }
        }

        $this->registerMiddleware($middlewareObject);

        $this->middlewareCollection->addPathMiddleware($pathObject, $middlewareObject);

        return $this;
    }

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnTag($tag, $middleware) : RouterInterface
    {
        $tagObject = Tag::from($tag);
        $middlewareObject = GenericHandlerMiddleware::from($middleware);

        $this->registerMiddleware($middlewareObject);

        $this->middlewareCollection->addTagMiddleware($tagObject, $middlewareObject);

        return $this;
    }

    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int
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
     * @param string                                    $path
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnPath($path, $fallback) : RouterInterface
    {
        $pathObject = Path::from($path);
        $fallbackObject = GenericHandlerFallback::from($fallback);

        if (! $this->config->compileTrailingSlashMode) {
            $pathValue = $pathObject->getValue();

            $isEndsWithSlash = true
                && ('/' !== $pathValue)
                && ('/' === $pathValue[ strlen($pathValue) - 1 ]);

            if ($isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_NEVER)) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $pathValue
                );

            } elseif (! $isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS)) {
                throw new RuntimeException(
                    'The `path` must end with `/` sign: ' . $pathValue
                );
            }
        }

        $this->registerFallback($fallbackObject);

        $this->fallbackCollection->addPathFallback($pathObject, $fallbackObject);

        return $this;
    }

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnTag($tag, $fallback) : RouterInterface
    {
        $tagObject = Tag::from($tag);
        $fallbackObject = GenericHandlerFallback::from($fallback);

        $this->registerFallback($fallbackObject);

        $this->fallbackCollection->addTagFallback($tagObject, $fallbackObject);

        return $this;
    }

    public function registerFallback(GenericHandlerFallback $fallback) : int
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
        $this->registerRouteGroup($this->rootRouterGroup);

        $this->rootRouterGroup = $this->routerFactory->newRouteGroup();

        return $this;
    }


    /**
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        RouterDispatcherContract $contract,
        $input = null,
        &$context = null,
        array $args = []
    )
    {
        return $this->routerDispatcher->dispatch(
            $contract,
            $input, $context, $args
        );
    }

    public function getDispatchContract() : RouterDispatcherContract
    {
        return $this->routerDispatcher->getDispatchContract();
    }

    public function getDispatchRequestMethod() : string
    {
        return $this->routerDispatcher->getDispatchRequestMethod();
    }

    public function getDispatchRequestUri() : string
    {
        return $this->routerDispatcher->getDispatchRequestUri();
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
     * @param int[] $ids
     *
     * @return Route[]
     */
    public function matchAllByIds($ids) : array
    {
        return $this->routerMatcher->matchAllByIds($ids);
    }

    public function matchFirstByIds($ids) : ?Route
    {
        return $this->routerMatcher->matchFirstByIds($ids);
    }

    /**
     * @param string[] $names
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames($names, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByNames($names, $unique);
    }

    public function matchFirstByNames($names) : ?Route
    {
        return $this->routerMatcher->matchFirstByNames($names);
    }

    /**
     * @param string[] $tags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags($tags, ?bool $unique = null) : array
    {
        return $this->routerMatcher->matchAllByTags($tags, $unique);
    }

    public function matchFirstByTags($tags) : ?Route
    {
        return $this->routerMatcher->matchFirstByTags($tags);
    }

    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContract $contract) : array
    {
        return $this->routerMatcher->matchByContract($contract);
    }


    /**
     * @param Route|Route[]|string|string[] $routes
     *
     * @return string[]
     */
    public function urls($routes, array $attributes = []) : array
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
        if (null === ($path = $routeBlueprint->path)) {
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

        if (null === $routeBlueprint->httpMethodIndex) {
            throw new RuntimeException(
                [
                    'Missing `method` in route',
                    $routeBlueprint,
                ]
            );
        }

        $pathValue = $path->getValue();

        if (! $this->config->compileTrailingSlashMode) {
            $isEndsWithSlash = true
                && ('/' !== $pathValue)
                && ('/' === $pathValue[ strlen($pathValue) - 1 ]);

            if ($isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_NEVER)) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $pathValue
                );

            } elseif (! $isEndsWithSlash && ($this->config->compileTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS)) {
                throw new RuntimeException(
                    'The `path` must end with `/` sign: ' . $pathValue
                );
            }
        }

        $pathRegex = $this->compilePathRegex($pathValue, $attributesIndex);

        $route = $this->routerFactory->newRoute();

        $route->path = $pathValue;
        $route->compiledPathRegex = $pathRegex;

        $route->action = $routeBlueprint->action;
        $route->compiledActionAttributes = array_fill_keys(array_keys($attributesIndex), null);

        if (null !== $routeBlueprint->name) {
            $route->name = $routeBlueprint->name->getValue();
        }

        $route->httpMethodIndex = $routeBlueprint->httpMethodIndex;
        $route->tagIndex = $routeBlueprint->tagIndex;

        if ($routeBlueprint->middlewareDict) {
            foreach ( $routeBlueprint->middlewareDict as $middleware ) {
                $this->middlewareOnPath($pathValue, $middleware);
            }
        }

        if ($routeBlueprint->fallbackDict) {
            foreach ( $routeBlueprint->fallbackDict as $fallback ) {
                $this->fallbackOnPath($pathValue, $fallback);
            }
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

        if (null === Lib::parse()->regex('/^' . $pathRegex . '$/')) {
            throw new RuntimeException(
                'The output regex is not valid: ' . $pathRegex
            );
        }

        $attributesIndex = $attributesIndex ?? [];

        return $pathRegex;
    }
}
