<?php

namespace Gzhegow\Router;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\Struct\Tag;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Core\Config\RouterConfig;
use Gzhegow\Router\Exception\LogicException;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Core\Cache\RouterCacheInterface;
use Gzhegow\Router\Core\Contract\RouterMatchContract;
use Gzhegow\Router\Exception\Runtime\NotFoundException;
use Gzhegow\Router\Core\Contract\RouterDispatchContract;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Exception\Exception\DispatchException;
use Gzhegow\Router\Core\Collection\RouterPatternCollection;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
use Gzhegow\Router\Core\Collection\RouterMiddlewareCollection;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;
use Gzhegow\Router\Package\Gzhegow\Pipeline\RouterPipelineFactoryInterface;
use Gzhegow\Router\Package\Gzhegow\Pipeline\ProcessManager\RouterProcessManagerInterface;


class RouterFacade implements RouterInterface
{
    /**
     * @var RouterFactoryInterface
     */
    protected $routerFactory;

    /**
     * @var RouterPipelineFactoryInterface
     */
    protected $pipelineFactory;
    /**
     * @var RouterProcessManagerInterface
     */
    protected $pipelineProcessManager;

    /**
     * @var RouterConfig
     */
    protected $config;

    /**
     * @var RouterCacheInterface
     */
    protected $routerCache;

    /**
     * @var RouterFallbackCollection
     */
    protected $fallbackCollection;
    /**
     * @var RouterMiddlewareCollection
     */
    protected $middlewareCollection;
    /**
     * @var RouterPatternCollection
     */
    protected $patternCollection;
    /**
     * @var RouterRouteCollection
     */
    protected $routeCollection;

    /**
     * @var RouterNode
     */
    protected $routerNodeRoot;
    /**
     * @var RouteGroup
     */
    protected $routeGroupRoot;

    /**
     * @var bool
     */
    protected $isRouterChanged = false;


    public function __construct(
        RouterFactoryInterface $routerFactory,
        RouterCacheInterface $routerCache,
        //
        RouterPipelineFactoryInterface $pipelineFactory,
        RouterProcessManagerInterface $pipelineProcessManager,
        //
        RouterConfig $config
    )
    {
        $this->routerFactory = $routerFactory;
        $this->routerCache = $routerCache;

        $this->pipelineFactory = $pipelineFactory;
        $this->pipelineProcessManager = $pipelineProcessManager;

        $this->fallbackCollection = $this->routerFactory->newFallbackCollection();
        $this->middlewareCollection = $this->routerFactory->newMiddlewareCollection();
        $this->patternCollection = $this->routerFactory->newPatternCollection();
        $this->routeCollection = $this->routerFactory->newRouteCollection();

        $this->config = $config;
        $this->config->validate();

        $routerNodeRoot = $this->routerFactory->newRouterNode();
        $routerNodeRoot->part = '/';
        $this->routerNodeRoot = $routerNodeRoot;

        $this->routeGroupRoot = $this->routerFactory->newRouteGroup();
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
            'routerNodeRoot'       => $this->routerNodeRoot,
        ];

        $this->routerCache->saveCache($cacheData);

        $this->isRouterChanged = false;

        return $this;
    }


    public function newBlueprint(RouteBlueprint $from = null) : RouteBlueprint
    {
        $routeBlueprint = $this->routeGroupRoot->newBlueprint($from);

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
        RouteBlueprint $from = null,
        $path = null, $httpMethods = null, $action = null, $name = null, $tags = null
    ) : RouteBlueprint
    {
        $routeBlueprint = $this->routeGroupRoot->blueprint(
            $from,
            $path, $httpMethods, $action, $name, $tags
        );

        return $routeBlueprint;
    }



    public function group(RouteBlueprint $from = null) : RouteGroup
    {
        $routeGroup = $this->routeGroupRoot->group($from);

        return $routeGroup;
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
        $routeGroup = $this->routeGroupRoot->route(
            $path, $httpMethods, $action,
            $name, $tags
        );

        return $routeGroup;
    }

    public function addRoute(RouteBlueprint $routeBlueprint) : RouterInterface
    {
        $this->routeGroupRoot->addRoute($routeBlueprint);

        return $this;
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

            $isEndsWithSlash = ('/' === $pathValue[ strlen($pathValue) - 1 ]);

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

            $isEndsWithSlash = ('/' === $pathValue[ strlen($pathValue) - 1 ]);

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


    public function commit() : RouterInterface
    {
        $this->registerRouteGroup($this->routeGroupRoot);

        $this->routeGroupRoot = $this->routerFactory->newRouteGroup();

        return $this;
    }


    /**
     * @param int[] $ids
     *
     * @return Route[]
     */
    public function matchAllByIds($ids) : array
    {
        $result = [];

        $_ids = (array) $ids;

        $routeList = $this->routeCollection->routeList;

        foreach ( $_ids as $id ) {
            if (isset($routeList[ $id ])) {
                $result[ $id ] = $routeList[ $id ];
            }
        }

        return $result;
    }

    public function matchFirstByIds($ids) : ?Route
    {
        $result = null;

        $_ids = (array) $ids;

        $routeList = $this->routeCollection->routeList;

        foreach ( $_ids as $id ) {
            if (isset($routeList[ $id ])) {
                $result = $routeList[ $id ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param string[] $names
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames($names, bool $unique = null) : array
    {
        $result = [];

        $_names = (array) $names;
        $_unique = $unique ?? false;

        $routeIndexByName = $this->routeCollection->routeIndexByName;

        $matchIndex = [];
        $namesIndex = [];
        foreach ( $_names as $idx => $name ) {
            $result[ $idx ] = [];

            if (isset($routeIndexByName[ $name ])) {
                $matchIndex += $routeIndexByName[ $name ];
            }

            if (! $_unique) {
                $namesIndex[ $name ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        if ($_unique) {
            $result = $routesMatch;

        } else {
            foreach ( $routesMatch as $route ) {
                /** @var Route $route */

                foreach ( $namesIndex[ $route->name ] ?? [] as $idx => $bool ) {
                    $result[ $idx ][ $route->id ] = $route;
                }
            }
        }

        return $result;
    }

    public function matchFirstByNames($names) : ?Route
    {
        $result = null;

        $_names = (array) $names;

        $routeIndexByName = $this->routeCollection->routeIndexByName;

        $matchIndex = [];
        foreach ( $_names as $name ) {
            if (isset($routeIndexByName[ $name ])) {
                $matchIndex += $routeIndexByName[ $name ];
            }

            if (count($matchIndex)) {
                $result = $this->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param string[] $tags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags($tags, bool $unique = null) : array
    {
        $result = [];

        $_tags = (array) $tags;
        $_unique = $unique ?? false;

        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        $tagsIndex = [];
        foreach ( $_tags as $idx => $tag ) {
            $result[ $idx ] = [];

            if (isset($routeIndexByTag[ $tag ])) {
                $matchIndex += $routeIndexByTag[ $tag ];
            }

            if (! $_unique) {
                $tagsIndex[ $tag ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        if ($_unique) {
            $result = $routesMatch;

        } else {
            foreach ( $routesMatch as $route ) {
                /** @var Route $route */

                foreach ( $route->tagIndex as $tag => $b ) {
                    foreach ( $tagsIndex[ $tag ] ?? [] as $idx => $bb ) {
                        $result[ $idx ][ $route->id ] = $route;
                    }
                }
            }
        }

        return $result;
    }

    public function matchFirstByTags($tags) : ?Route
    {
        $result = null;

        $_tags = (array) $tags;

        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        foreach ( $_tags as $tag ) {
            if (isset($routeIndexByTag[ $tag ])) {
                $matchIndex += $routeIndexByTag[ $tag ];
            }

            if (count($matchIndex)) {
                $result = $this->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatchContract $contract) : array
    {
        $intersect = [];

        if ($contract->idIndex) {
            $intersect[] = $contract->idIndex;
        }

        if ($contract->nameIndex) {
            $index = [];
            foreach ( $contract->nameIndex as $name => $bool ) {
                $index += $this->routeCollection->routeIndexByName[ $name ] ?? [];
            }

            $intersect[] = $index;
        }

        if ($contract->tagIndex) {
            $index = [];
            foreach ( $contract->tagIndex as $tag => $bool ) {
                $index += $this->routeCollection->routeIndexByTag[ $tag ] ?? [];
            }

            $intersect[] = $index;
        }

        if ([] !== $intersect) {
            $index = (count($intersect) > 1)
                ? array_intersect_key(...$intersect)
                : $intersect;

        } else {
            $index = array_fill_keys(
                array_keys($this->routeCollection->routeList),
                true
            );
        }

        $hasHttpMethodIndex = ! empty($contract->httpMethodIndex);
        $hasPathIndex = ! empty($contract->pathIndex);

        $result = [];

        foreach ( $index as $id => $b ) {
            $route = $this->routeCollection->routeList[ $id ];

            if ($hasHttpMethodIndex) {
                if (! array_intersect_key($route->httpMethodIndex, $contract->httpMethodIndex)) {
                    continue;
                }
            }

            if ($hasPathIndex) {
                $found = false;
                foreach ( $contract->pathIndex as $path => $bb ) {
                    $found = (0 === strpos($route->path, $path));

                    if ($found) break;
                }

                if (! $found) continue;
            }

            $result[ $id ] = $route;
        }

        return $result;
    }


    /**
     * @return mixed
     * @throws DispatchException
     */
    public function dispatch(
        RouterDispatchContract $contract,
        $input = null, $context = null
    )
    {
        $contractHttpMethod = $contract->httpMethod->getValue();
        $contractRequestUri = $contract->requestUri;

        $dispatchHttpMethod = $contractHttpMethod;
        if ($this->config->dispatchForceMethod) {
            $dispatchHttpMethod = $this->config->dispatchForceMethod;
        }

        $dispatchRequestUri = $contractRequestUri;
        if ($this->config->dispatchTrailingSlashMode) {
            $dispatchRequestUri = rtrim($dispatchRequestUri, '/');

            if ($this->config->dispatchTrailingSlashMode === Router::TRAILING_SLASH_ALWAYS) {
                $dispatchRequestUri = $dispatchRequestUri . '/';
            }
        }

        $dispatchActionAttributes = [];

        $routeNodeCurrent = $this->routerNodeRoot;

        $middlewareIndexes = [
            'path' => [],
            'tags' => [],
        ];
        $fallbackIndexes = [
            'path' => [],
            'tags' => [],
        ];

        $indexMatch = null;
        $pathCurrent = '';

        $slice = $dispatchRequestUri;
        $slice = trim($slice, '/');
        $slice = explode('/', $slice);
        while ( $slice ) {
            $part = array_shift($slice);

            $isRoute = empty($slice);

            if ($isRoute) {
                if (isset($routeNodeCurrent->routeIndexByPart[ $part ])) {
                    $indexMatch = $routeNodeCurrent->routeIndexByPart[ $part ];

                    break;
                }

                foreach ( $routeNodeCurrent->routeIndexByRegex as $regex => $routeIndex ) {
                    if (preg_match('/^' . $regex . '$/', $part, $matches)) {
                        $indexMatch = $routeIndex;

                        foreach ( $matches as $key => $value ) {
                            if (is_string($key)) {
                                $dispatchActionAttributes[ $key ] = $value;
                            }
                        }

                        break 2;
                    }
                }

            } else {
                if (isset($routeNodeCurrent->childrenByPart[ $part ])) {
                    $routeNodeCurrent = $routeNodeCurrent->childrenByPart[ $part ];

                    $pathCurrent .= '/' . $routeNodeCurrent->part;

                    if (isset($this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ])) {
                        $middlewareIndexes[ 'path' ][ $pathCurrent ] = $this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ];
                    }

                    if (isset($this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ])) {
                        $fallbackIndexes[ 'path' ][ $pathCurrent ] = $this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ];
                    }

                    continue;
                }

                foreach ( $routeNodeCurrent->childrenByRegex as $regex => $routeNode ) {
                    if (preg_match('/^' . $regex . '$/', $part, $matches)) {
                        $routeNodeCurrent = $routeNode;

                        $pathCurrent .= '/' . $routeNodeCurrent->part;

                        if (isset($this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ])) {
                            $middlewareIndexes[ 'path' ][ $pathCurrent ] = $this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ];
                        }

                        if (isset($this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ])) {
                            $fallbackIndexes[ 'path' ][ $pathCurrent ] = $this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ];
                        }

                        foreach ( $matches as $key => $value ) {
                            if (is_string($key)) {
                                $dispatchActionAttributes[ $key ] = $value;
                            }
                        }

                        continue 2;
                    }
                }
            }
        }

        $routeFoundId = null;
        if (null !== $indexMatch) {
            $intersect = [];

            $intersect[] = $indexMatch;

            if (! $this->config->dispatchIgnoreMethod) {
                $intersect[] = $routeNodeCurrent->routeIndexByMethod[ $dispatchHttpMethod ] ?? [];
            }

            $indexMatch = array_intersect_key(...$intersect);

            if ($indexMatch) {
                $routeFoundId = key($indexMatch);
            }
        }

        $routeFoundClone = null;
        if (null !== $routeFoundId) {
            $routeFoundClone = clone $this->routeCollection->routeList[ $routeFoundId ];
        }

        if (null !== $routeFoundClone) {
            $routePath = $routeFoundClone->path;

            if (isset($this->middlewareCollection->middlewareIndexByPath[ $routePath ])) {
                $middlewareIndexes[ 'path' ][ $routePath ] = $this->middlewareCollection->middlewareIndexByPath[ $routePath ];
            }

            foreach ( $routeFoundClone->tagIndex as $tag => $bool ) {
                if (isset($this->middlewareCollection->middlewareIndexByTag[ $tag ])) {
                    $middlewareIndexes[ 'tags' ] += $this->middlewareCollection->middlewareIndexByTag[ $tag ];
                }
            }

            if (isset($this->fallbackCollection->fallbackIndexByPath[ $routePath ])) {
                $fallbackIndexes[ 'path' ][ $routePath ] = $this->fallbackCollection->fallbackIndexByPath[ $routePath ];
            }

            foreach ( $routeFoundClone->tagIndex as $tag => $bool ) {
                if (isset($this->fallbackCollection->fallbackIndexByTag[ $tag ])) {
                    $fallbackIndexes[ 'tags' ] += $this->fallbackCollection->fallbackIndexByTag[ $tag ];
                }
            }
        }

        $fnSort = static function ($a, $b) {
            return strlen($b) <=> strlen($a);
        };

        uksort($middlewareIndexes[ 'path' ], $fnSort);
        uksort($fallbackIndexes[ 'path' ], $fnSort);

        $middlewareIndex = [];
        $fallbackIndex = [];

        foreach ( $middlewareIndexes[ 'path' ] as $index ) {
            $middlewareIndex += $index;
        }
        foreach ( $fallbackIndexes[ 'path' ] as $index ) {
            $fallbackIndex += $index;
        }

        $middlewareIndex += $middlewareIndexes[ 'tags' ];
        $fallbackIndex += $fallbackIndexes[ 'tags' ];

        /**
         * @var GenericHandlerMiddleware[] $middlewareList
         * @var GenericHandlerFallback[]   $fallbackList
         */

        $middlewareList = [];
        foreach ( $middlewareIndex as $i => $bool ) {
            $middlewareList[ $i ] = $this->middlewareCollection->middlewareList[ $i ];
        }

        $fallbackList = [];
        foreach ( $fallbackIndex as $i => $bool ) {
            $fallbackList[ $i ] = $this->fallbackCollection->fallbackList[ $i ];
        }

        $pipeline = $this->pipelineFactory->newPipeline();

        $chain = $pipeline;
        foreach ( $middlewareList as $middleware ) {
            $chain = $chain->startMiddleware($middleware);
        }

        if ($routeFoundClone) {
            $routeFoundClone->dispatchActionAttributes = $dispatchActionAttributes;

            $routeFoundClone->dispatchMiddlewareIndex = [];
            foreach ( $middlewareList as $middleware ) {
                $routeFoundClone->dispatchMiddlewareIndex[ $middleware->getKey() ] = true;
            }

            $routeFoundClone->dispatchFallbackIndex = [];
            foreach ( $fallbackList as $fallback ) {
                $routeFoundClone->dispatchFallbackIndex[ $fallback->getKey() ] = true;
            }

            $chain->action($routeFoundClone->action);

        } else {
            $throwable = new NotFoundException(
                'Route not found: '
                . "`{$contractRequestUri}`"
                . " / `{$dispatchHttpMethod}`"
            );

            $chain->throwable($throwable);
        }

        for ( $i = 0; $i < count($middlewareList); $i++ ) {
            $chain = $chain->endMiddleware();
        }

        foreach ( $fallbackList as $fallback ) {
            $chain->fallback($fallback);
        }

        try {
            $result = $this->pipelineProcessManager->run($pipeline, $input, $context);
        }
        catch ( \Gzhegow\Pipeline\Exception\Runtime\PipelineException $throwable ) {
            throw new DispatchException(
                'Unhandled exception occured during dispatch', $throwable
            );
        }

        return $result;
    }


    /**
     * @param Route|Route[]|string|string[] $routes
     *
     * @return string[]
     */
    public function urls($routes, array $attributes = []) : array
    {
        $routes = $routes ?? [];
        $routes = is_object($routes) ? [ $routes ] : (array) $routes;

        $result = [];

        $_routes = [];
        $_routeNames = [];
        foreach ( $routes as $idx => $route ) {
            $result[ $idx ] = null;

            if (is_object($route)) {
                $_routes[ $idx ] = $route;

            } elseif (null !== ($_name = Lib::parse()->string_not_empty($route))) {
                $_routeNames[ $idx ] = $_name;

            } else {
                throw new LogicException(
                    [
                        'Each of `routes` should be string as route `name` or object of class: ' . Route::class,
                        $routes,
                    ]
                );
            }
        }

        if ($_routeNames) {
            $batch = $this->matchAllByNames($_routeNames);

            foreach ( $batch as $idx => $items ) {
                if ($items) {
                    $_routes[ $idx ] = reset($items);
                }
            }
        }

        foreach ( $_routes as $idx => $route ) {
            $attributesCurrent = $this->generateUrlAttributes($route, $attributes, $idx);

            $result[ $idx ] = $this->generateUrl($route, $attributesCurrent);
        }

        return $result;
    }

    /**
     * @param Route|string $route
     */
    public function url($route, array $attributes = []) : string
    {
        [ $url ] = $this->urls([ $route ], $attributes);

        return $url;
    }

    protected function generateUrl(Route $route, array $attributes = []) : string
    {
        $attributesCurrent = $this->generateUrlAttributes($route, $attributes);

        $search = [];
        foreach ( $attributesCurrent as $key => $attr ) {
            $search[ '{' . $key . '}' ] = $attr;
        }

        $url = str_replace(
            array_keys($search),
            array_values($search),
            $route->path
        );

        return $url;
    }

    protected function generateUrlAttributes(Route $route, array $attributes = [], $idx = null) : array
    {
        $result = [];

        foreach ( $route->compiledActionAttributes as $key => $attr ) {
            $attr = $attributes[ $key ][ $idx ] ?? $attributes[ $key ] ?? $attr;

            if (null === $attr) {
                throw new RuntimeException(
                    [
                        'Missing attributes: '
                        . "attributes[{$key}][{$idx}], attributes[{$key}]",
                        $attributes,
                    ]
                );
            }

            $result[ $key ] = $attr;
        }

        return $result;
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


    public function registerRoute(Route $route) : int
    {
        $this->isRouterChanged = true;

        if (! $this->config->registerAllowObjectsAndClosures) {
            $runtimeAction = null
                ?? $route->action->hasClosureObject()
                ?? $route->action->hasMethodObject()
                ?? $route->action->hasInvokableObject();

            if (null !== $runtimeAction) {
                throw new RuntimeException(
                    [
                        'The `action` should not be runtime object or \Closure',
                        $runtimeAction,
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
            $routeNodePrevious = $routeNodePrevious ?? $this->routerNodeRoot;

            $part = array_shift($slice);

            $isPattern = (false !== strpos($part, Router::PATTERN_ENCLOSURE[ 0 ]));
            $isRoute = empty($slice);

            $partRegex = null;
            if ($isPattern) {
                $partRegex = $this->compilePathRegex($part);
            }

            if ($isRoute) {
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

    public function registerPattern(RouterPattern $pattern) : string
    {
        $this->isRouterChanged = true;

        if (isset($this->patternDict[ $pattern->pattern ])) {
            throw new RuntimeException(
                'The `pattern` is already exists: ' . $pattern->pattern
            );
        }

        $id = $this->patternCollection->registerPattern($pattern);

        return $id;
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
            $isEndsWithSlash = ('/' === $pathValue[ strlen($pathValue) - 1 ]);

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

    protected function compilePathRegex(string $path, array &$attributesIndex = null) : string
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
