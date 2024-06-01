<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Pattern\Pattern;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\Struct\Tag;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Route\Struct\Path;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Route\Struct\HttpMethod;
use Gzhegow\Router\Exception\LogicException;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Cache\RouterCacheInterface;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Contract\RouterMatchContract;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Contract\RouterDispatchContract;
use Gzhegow\Router\Collection\MiddlewareCollection;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Exception\Runtime\NotFoundException;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


class Router implements RouterInterface
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


    /**
     * @var RouterFactoryInterface
     */
    protected $factory;
    /**
     * @var RouterProcessorInterface
     */
    protected $processor;
    /**
     * @var RouterCacheInterface
     */
    protected $cache;

    /**
     * > gzhegow, false -> чтобы работал кеш (т.к. объекты runtime и замыкания нельзя сохранить в файл)
     *
     * @var bool
     */
    protected $registerAllowObjectsAndClosures = false;
    /**
     * > gzhegow, -1/1 -> чтобы бросать исключение при попытке зарегистрировать роут без/с trailing-slash
     *
     * @var int
     */
    protected $compileTrailingSlashMode = self::TRAILING_SLASH_AS_IS;
    /**
     * > gzhegow, true -> чтобы не учитывать метод запроса при выполнении маршрута, удобно тестировать POST/OPTIONS/HEAD запросы в браузере (сработает первый зарегистрированный!)
     *
     * @var bool
     */
    protected $dispatchIgnoreMethod = false;
    /**
     * > gzhegow, 'GET|POST|PUT|OPTIONS' etc, чтобы принудительно установить метод запроса при выполнении действия
     *
     * @var string
     */
    protected $dispatchForceMethod = null;
    /**
     * > gzhegow, -1/1 -> чтобы автоматически доставлять или убирать trailing-slash на этапе выполнения
     *
     * @var bool
     */
    protected $dispatchTrailingSlashMode = self::TRAILING_SLASH_AS_IS;

    /**
     * @var bool
     */
    protected $isRouterChanged = false;

    /**
     * @var RouteCollection
     */
    protected $routeCollection;
    /**
     * @var MiddlewareCollection
     */
    protected $middlewareCollection;
    /**
     * @var FallbackCollection
     */
    protected $fallbackCollection;
    /**
     * @var PatternCollection
     */
    protected $patternCollection;

    /**
     * @var RouterNode
     */
    protected $routerNodeRoot;

    /**
     * @var RouteGroup
     */
    protected $routeGroupCurrent;


    public function __construct(
        RouterFactoryInterface $factory,
        RouterProcessorInterface $processor,
        RouterCacheInterface $cache
    )
    {
        $this->factory = $factory;
        $this->processor = $processor;
        $this->cache = $cache;

        $this->routeCollection = $this->factory->newRouteCollection();
        $this->middlewareCollection = $this->factory->newMiddlewareCollection();
        $this->fallbackCollection = $this->factory->newFallbackCollection();
        $this->patternCollection = $this->factory->newPatternCollection();

        $routerNodeRoot = $this->factory->newRouteNode();
        $routerNodeRoot->part = '/';
        $this->routerNodeRoot = $routerNodeRoot;
    }


    /**
     * @param bool|null   $registerAllowObjectsAndClosures
     * @param int|null    $compileTrailingSlashMode  @see \Gzhegow\Router\Router::LIST_TRAILING_SLASH
     * @param bool|null   $dispatchIgnoreMethod
     * @param string|null $dispatchForceMethod       @see \Gzhegow\Router\Route\Struct\HttpMethod::LIST_METHOD
     * @param int|null    $dispatchTrailingSlashMode @see \Gzhegow\Router\Router::LIST_TRAILING_SLASH
     */
    public function setSettings(
        bool $registerAllowObjectsAndClosures = null,
        int $compileTrailingSlashMode = null,
        bool $dispatchIgnoreMethod = null,
        string $dispatchForceMethod = null,
        int $dispatchTrailingSlashMode = null
    ) // : static
    {
        $registerAllowObjectsAndClosures = $registerAllowObjectsAndClosures ?? false;
        $compileTrailingSlashMode = $compileTrailingSlashMode ?? self::TRAILING_SLASH_AS_IS;
        $dispatchIgnoreMethod = $dispatchIgnoreMethod ?? false;
        $dispatchForceMethod = $dispatchForceMethod ?? null;
        $dispatchTrailingSlashMode = $dispatchTrailingSlashMode ?? self::TRAILING_SLASH_AS_IS;

        if (! isset(static::LIST_TRAILING_SLASH[ $compileTrailingSlashMode ])) {
            throw new LogicException(
                'The `compileTrailingSlashMode` should be one of: ' . implode(',', array_keys(static::LIST_TRAILING_SLASH))
            );
        }

        if (! isset(static::LIST_TRAILING_SLASH[ $dispatchTrailingSlashMode ])) {
            throw new LogicException(
                'The `dispatchTrailingSlashMode` should be one of: ' . implode(',', array_keys(static::LIST_TRAILING_SLASH))
            );
        }

        if (null !== $dispatchForceMethod) {
            $dispatchForceMethod = HttpMethod::from($dispatchForceMethod)->getValue();
        }

        $this->registerAllowObjectsAndClosures = $registerAllowObjectsAndClosures;
        $this->compileTrailingSlashMode = $compileTrailingSlashMode;
        $this->dispatchIgnoreMethod = $dispatchIgnoreMethod;
        $this->dispatchForceMethod = $dispatchForceMethod;
        $this->dispatchTrailingSlashMode = $dispatchTrailingSlashMode;

        return $this;
    }

    /**
     * @param array{
     *     cacheMode: string|null,
     *     cacheAdapter: object|\Psr\Cache\CacheItemPoolInterface|null,
     *     cacheDirpath: string|null,
     *     cacheFilename: string|null,
     * }|null $settings
     */
    public function setCacheSettings(array $settings = null) // : static
    {
        $cacheMode = $settings[ 'cacheMode' ] ?? $settings[ 0 ] ?? null;
        $cacheAdapter = $settings[ 'cacheAdapter' ] ?? $settings[ 1 ] ?? null;
        $cacheDirpath = $settings[ 'cacheDirpath' ] ?? $settings[ 2 ] ?? null;
        $cacheFilename = $settings[ 'cacheFilename' ] ?? $settings[ 3 ] ?? null;

        $this->cache->setCacheSettings(
            $cacheMode,
            $cacheAdapter,
            $cacheDirpath,
            $cacheFilename
        );

        return $this;
    }


    public function cacheClear() // : static
    {
        $this->cache->clearCache();

        return $this;
    }

    public function cacheRemember($fn) // : static
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

        $cacheData = $this->cache->loadCache();

        $status = (null !== $cacheData);

        if ($status) {
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
        }

        return $status;
    }

    protected function cacheSave() // : static
    {
        if (! $this->isRouterChanged) return $this;

        $cacheData = [
            'routeCollection'      => $this->routeCollection,
            'middlewareCollection' => $this->middlewareCollection,
            'fallbackCollection'   => $this->fallbackCollection,
            'patternCollection'    => $this->patternCollection,
            'routerNodeRoot'       => $this->routerNodeRoot,
        ];

        $this->cache->saveCache($cacheData);

        $this->isRouterChanged = false;

        return $this;
    }


    /**
     * @param string[] $names
     *
     * @return Route[][]
     */
    public function matchAllByNames(array $names) : array
    {
        $result = [];

        $namesIndex = [];

        $indexMatch = [];
        foreach ( $names as $idx => $name ) {
            $result[ $idx ] = [];

            $namesIndex[ $name ][ $idx ] = true;

            if (isset($this->routeCollection->routeIndexByName[ $name ])) {
                $indexMatch += $this->routeCollection->routeIndexByName[ $name ];
            }
        }

        $routesMatch = [];
        foreach ( $indexMatch as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        foreach ( $routesMatch as $route ) {
            /** @var Route $route */

            foreach ( $namesIndex[ $route->name ] ?? [] as $idx => $bool ) {
                $result[ $idx ][ $route->id ] = $route;
            }
        }

        return $result;
    }

    public function matchFirstByName(string $name, array $optional = [], array &$routes = null) : ?Route
    {
        $routes = null;

        $all = $optional[ 'all' ] ?? $optional[ 0 ] ?? false;

        $index = $this->routeCollection->routeIndexByName[ $name ] ?? [];

        $result = null;

        if ($index) {
            $result = $this->routeCollection->routeList[ key($index) ];

            if ($all) {
                foreach ( $index as $id => $bool ) {
                    $routes[ $id ] = $this->routeCollection->routeList[ $id ];
                }
            }
        }

        return $result;
    }


    /**
     * @param string[] $tags
     *
     * @return Route[][]
     */
    public function matchAllByTags(array $tags) : array
    {
        $result = [];

        $tagsIndex = [];

        $indexMatch = [];
        foreach ( $tags as $idx => $tag ) {
            $result[ $idx ] = [];

            $tagsIndex[ $tag ][ $idx ] = true;

            if (isset($this->routeCollection->routeIndexByTag[ $tag ])) {
                $indexMatch += $this->routeCollection->routeIndexByTag[ $tag ];
            }
        }

        $routesMatch = [];
        foreach ( $indexMatch as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        foreach ( $routesMatch as $route ) {
            /** @var Route $route */

            foreach ( $route->tagIndex ?? [] as $tag => $b ) {
                foreach ( $tagsIndex[ $tag ] ?? [] as $idx => $bb ) {
                    $result[ $idx ][ $route->id ] = $route;
                }
            }
        }

        return $result;
    }

    public function matchFirstByTag(string $tag, array $optional = [], array &$routes = null) : ?Route
    {
        $routes = null;

        $all = $optional[ 'all' ] ?? $optional[ 0 ] ?? false;

        $index = $this->routeCollection->routeIndexByTag[ $tag ] ?? [];

        $result = null;

        if ($index) {
            $result = $this->routeCollection->routeList[ key($index) ];

            if ($all) {
                foreach ( $index as $id => $bool ) {
                    $routes[ $id ] = $this->routeCollection->routeList[ $id ];
                }
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

        if ($intersect) {
            $index = Lib::array_intersect_key(...$intersect);

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
                if (! array_intersect_key($route->httpMethodIndex ?? [], $contract->httpMethodIndex)) {
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
     * @throws \Throwable
     */
    public function dispatch(
        RouterDispatchContract $contract,
        $input = null, $context = null
    ) // : mixed
    {
        $contractHttpMethod = $contract->httpMethod->getValue();
        $contractRequestUri = $contract->requestUri;
        $contractActionAttributes = [];

        if ($this->dispatchTrailingSlashMode) {
            $contractRequestUri = rtrim($contractRequestUri, '/');

            if ($this->dispatchTrailingSlashMode === static::TRAILING_SLASH_ALWAYS) {
                $contractRequestUri = $contractRequestUri . '/';
            }
        }

        $dispatchHttpMethod = $contractHttpMethod;

        if ($this->dispatchForceMethod) {
            $dispatchHttpMethod = $this->dispatchForceMethod;
        }

        $indexMatch = null;

        $middlewareIndex = [];
        $fallbackIndex = [];

        $routeNodeCurrent = $this->routerNodeRoot;

        $pathCurrent = '';

        $slice = $contractRequestUri;
        $slice = trim($slice, '/');
        $slice = explode('/', $slice);
        while ( $slice ) {
            $part = array_shift($slice);

            if (isset($routeNodeCurrent->routeIndexByPart[ $part ])) {
                $indexMatch = $routeNodeCurrent->routeIndexByPart[ $part ];

                break;
            }

            if (isset($routeNodeCurrent->childrenByPart[ $part ])) {
                $routeNodeCurrent = $routeNodeCurrent->childrenByPart[ $part ];

                $pathCurrent .= '/' . $routeNodeCurrent->part;

                if (isset($this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ])) {
                    $middlewareIndex += $this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ];
                }

                if (isset($this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ])) {
                    $fallbackIndex += $this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ];
                }

                continue;
            }

            foreach ( $routeNodeCurrent->routeIndexByRegex ?? [] as $regex => $routeIndex ) {
                if (preg_match('/^' . $regex . '$/', $part, $matches)) {
                    $indexMatch = $routeIndex;

                    foreach ( $matches as $key => $value ) {
                        if (is_string($key)) {
                            $contractActionAttributes[ $key ] = $value;
                        }
                    }

                    break 2;
                }
            }

            foreach ( $routeNodeCurrent->childrenByRegex ?? [] as $regex => $routeNode ) {
                if (preg_match('/^' . $regex . '$/', $part, $matches)) {
                    $routeNodeCurrent = $routeNode;

                    $pathCurrent .= '/' . $routeNodeCurrent->part;

                    if (isset($this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ])) {
                        $middlewareIndex += $this->middlewareCollection->middlewareIndexByPath[ $pathCurrent ];
                    }

                    if (isset($this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ])) {
                        $fallbackIndex += $this->fallbackCollection->fallbackIndexByPath[ $pathCurrent ];
                    }

                    foreach ( $matches as $key => $value ) {
                        if (is_string($key)) {
                            $contractActionAttributes[ $key ] = $value;
                        }
                    }

                    continue 2;
                }
            }
        }

        $routeCurrentId = null;
        if (null !== $indexMatch) {
            $intersect = [];

            $intersect[] = $indexMatch;

            if (! $this->dispatchIgnoreMethod) {
                $intersect[] = $routeNodeCurrent->routeIndexByMethod[ $dispatchHttpMethod ] ?? [];
            }

            $indexMatch = array_intersect_key(...$intersect);

            if ($indexMatch) {
                $routeCurrentId = key($indexMatch);
            }
        }

        $routeCurrentClone = null;

        if (null !== $routeCurrentId) {
            $routeCurrentClone = clone $this->routeCollection->routeList[ $routeCurrentId ];

            $routePath = $routeCurrentClone->path;

            if (isset($this->middlewareCollection->middlewareIndexByPath[ $routePath ])) {
                $middlewareIndex += $this->middlewareCollection->middlewareIndexByPath[ $routePath ];
            }

            foreach ( $routeCurrentClone->tagIndex ?? [] as $tag => $bool ) {
                if (isset($this->middlewareCollection->middlewareIndexByTag[ $tag ])) {
                    $middlewareIndex += $this->middlewareCollection->middlewareIndexByTag[ $tag ];
                }
            }

            if (isset($this->fallbackCollection->fallbackIndexByPath[ $routePath ])) {
                $fallbackIndex += $this->fallbackCollection->fallbackIndexByPath[ $routePath ];
            }

            foreach ( $routeCurrentClone->tagIndex ?? [] as $tag => $bool ) {
                if (isset($this->fallbackCollection->fallbackIndexByTag[ $tag ])) {
                    $fallbackIndex += $this->fallbackCollection->fallbackIndexByTag[ $tag ];
                }
            }
        }

        /** @var GenericMiddleware[] $middlewareList */
        $middlewareList = array_intersect_key($this->middlewareCollection->middlewareList ?? [], $middlewareIndex);

        /** @var GenericFallback[] $fallbackList */
        $fallbackList = array_intersect_key($this->fallbackCollection->fallbackList ?? [], $fallbackIndex);

        ksort($middlewareList);
        ksort($fallbackList);

        $pipeline = $this->factory->newPipeline($this->processor);

        if ($routeCurrentClone) {
            $routeCurrentClone->contractActionAttributes = $contractActionAttributes;

            $routeCurrentClone->contractMiddlewareIndex = [];
            foreach ( $middlewareList as $middleware ) {
                $routeCurrentClone->contractMiddlewareIndex[ $middleware->getKey() ] = true;
            }

            $routeCurrentClone->contractFallbackIndex = [];
            foreach ( $fallbackList as $fallback ) {
                $routeCurrentClone->contractFallbackIndex[ $fallback->getKey() ] = true;
            }

            $pipeline
                ->addMiddlewares($middlewareList)
                ->addAction($routeCurrentClone->action)
                ->addFallbacks($fallbackList)
            ;

            $result = $pipeline->runRoute(
                $routeCurrentClone,
                $input, $context
            );

        } else {
            $throwable = new NotFoundException(
                'Route not found: '
                . '`' . $contractRequestUri . '`'
                . ' / ' . '`' . $dispatchHttpMethod . '`'
            );

            $pipeline
                ->addFallbacks($fallbackList)
            ;

            $result = $pipeline->runThrowable(
                $throwable,
                $input, $context
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

            } elseif (null !== ($_name = Lib::filter_string($route))) {
                $_routeNames[ $idx ] = $_name;

            } else {
                throw new LogicException(
                    'Each of `routes` should be string as route `name` or object of class: ' . Route::class
                    . ' / ' . Lib::php_dump($routes)
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
            $hasRouteName = ! empty($route->name);

            $attributesCurrent = $this->generateUrlAttributes($route, $attributes, $idx);

            $result[ $idx ] = $this->generateUrl($route, $attributesCurrent);
        }

        return $result;
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
                    'Missing attributes: '
                    . "attributes[{$key}][{$idx}]"
                    . ', ' . "attributes[{$key}]"
                    . ' / ' . Lib::php_dump($attributes)
                );
            }

            $result[ $key ] = $attr;
        }

        return $result;
    }


    /**
     * @param string $pattern
     * @param string $regex
     */
    public function pattern($pattern, $regex) // : static
    {
        $pattern = Pattern::from([ $pattern, $regex ]);

        $this->registerPattern($pattern);

        return $this;
    }


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnPath($path, $middleware) // : static
    {
        $path = Path::from($path);
        $middleware = GenericMiddleware::from($middleware);

        if ($this->compileTrailingSlashMode) {
            $pathValue = $path->getValue();

            $isEndsWithSlash = ('/' === $pathValue[ strlen($pathValue) - 1 ]);

            if ($isEndsWithSlash && ($this->compileTrailingSlashMode === static::TRAILING_SLASH_NEVER)) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $pathValue
                );

            } elseif (! $isEndsWithSlash && ($this->compileTrailingSlashMode === static::TRAILING_SLASH_ALWAYS)) {
                throw new RuntimeException(
                    'The `path` must end with `/` sign: ' . $pathValue
                );
            }
        }

        $this->registerMiddleware($middleware);

        $this->middlewareCollection->addPathMiddleware($path, $middleware);

        return $this;
    }

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnTag($tag, $middleware) // : static
    {
        $tag = Tag::from($tag);
        $middleware = GenericMiddleware::from($middleware);

        $this->registerMiddleware($middleware);

        $this->middlewareCollection->addTagMiddleware($tag, $middleware);

        return $this;
    }

    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnPath($path, $fallback) // : static
    {
        $path = Path::from($path);
        $fallback = GenericFallback::from($fallback);

        if (! $this->compileTrailingSlashMode) {
            $pathValue = $path->getValue();

            if ('/' === $pathValue[ strlen($pathValue) - 1 ]) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $pathValue
                );
            }
        }

        $this->registerFallback($fallback);

        $this->fallbackCollection->addPathFallback($path, $fallback);

        return $this;
    }

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnTag($tag, $fallback) // : static
    {
        $tag = Tag::from($tag);
        $fallback = GenericFallback::from($fallback);

        $this->registerFallback($fallback);

        $this->fallbackCollection->addTagFallback($tag, $fallback);

        return $this;
    }


    public function blueprint(RouteBlueprint $from = null) : RouteBlueprint
    {
        if ($from) {
            $blueprint = clone $from;

        } elseif ($this->routeGroupCurrent) {
            $blueprint = clone $this->routeGroupCurrent->getRouteBlueprint();

        } else {
            $blueprint = $this->factory->newRouteBlueprint();
        }

        return $blueprint;
    }

    public function group(RouteBlueprint $from = null) : RouteGroup
    {
        if ($this->routeGroupCurrent) {
            throw new RuntimeException(
                'Unable to `' . __FUNCTION__ . '()` due to existing `groupCurrent`. '
                . 'You should avoid nesting groups to make your code less complex'
            );
        }

        $blueprint = $this->blueprint($from);

        $routeGroup = $this->factory->newRouteGroup($this, $blueprint);

        $this->routeGroupCurrent = $routeGroup;

        return $routeGroup;
    }


    /**
     * @param string                                    $path
     * @param string|string[]                           $httpMethods
     * @param callable|object|array|class-string|string $action
     */
    public function route($path, $httpMethods, $action, $name = null) : RouteBlueprint
    {
        if (! $this->routeGroupCurrent) {
            throw new RuntimeException(
                'Unable to `' . __FUNCTION__ . '()` due to empty `groupCurrent`. '
                . 'You have to call it inside `($group = $router->group())->register(function () { /* here */ })` callback'
            );
        }

        $blueprint = $this->blueprint();

        $_httpMethods = $httpMethods ?? [ 'GET' => true ];
        $_httpMethods = (array) $_httpMethods;

        $blueprint
            ->path($path)
            ->httpMethod($_httpMethods)
            ->action($action)
        ;

        if (null !== $name) {
            $blueprint->name($name);
        }

        $this->routeGroupCurrent->routeList[] = $blueprint;

        return $blueprint;
    }

    public function routeAdd($pathOrBlueprint, ...$arguments) // : static
    {
        array_unshift($arguments, $pathOrBlueprint);

        $from = null;

        is_a($pathOrBlueprint, RouteBlueprint::class)
            ? ([ $from, $path, $httpMethods, $action, $name ] = $arguments + [ null, null, null, null, null ])
            : ([ $path, $httpMethods, $action, $name ] = $arguments + [ null, null, null, null ]);

        $blueprint = $this->blueprint($from);

        $httpMethods = $httpMethods ?? [ 'GET' => true ];
        $httpMethods = (array) $httpMethods;

        if (null !== $path) {
            $blueprint->path($path);
        }

        if ($httpMethods) {
            $blueprint->httpMethod($httpMethods);
        }

        if (null !== $action) {
            $blueprint->action($action);
        }

        if (null !== $name) {
            $blueprint->name($name);
        }

        $route = $this->compileRoute($blueprint);

        $this->registerRoute($route);

        return $this;
    }


    protected function registerRouteGroup(RouteGroup $routeGroup) // : static
    {
        foreach ( $routeGroup->getRouteList() as $i => $routeBlueprint ) {
            $route = $this->compileRoute($routeBlueprint);

            $this->registerRoute($route);
        }

        unset($this->routeGroupCurrent);

        return $this;
    }

    protected function registerRoute(Route $route) // : static
    {
        $this->isRouterChanged = true;

        if (! $this->registerAllowObjectsAndClosures) {
            if ($route->action->closure || $route->action->methodObject || $route->action->invokableObject) {
                throw new RuntimeException(
                    'This route `action` should not be runtime object or \Closure: ' . Lib::php_dump($route)
                );
            }
        }

        $this->routeCollection->registerRoute($route);

        $path = $route->path;

        $slice = $path;
        $slice = trim($slice, '/');
        $slice = explode('/', $slice);
        while ( $slice ) {
            $routeNodePrevious = $routeNodePrevious ?? $this->routerNodeRoot;

            $part = array_shift($slice);

            $isPattern = (false !== strpos($part, static::PATTERN_ENCLOSURE[ 0 ]));
            $isRoute = empty($slice);

            $partCompiled = $part;
            if ($isPattern) {
                $partRegex = $this->compilePathRegex($part);

                $partCompiled = $partRegex;
            }

            if ($isRoute) {
                ($isPattern)
                    ? ($routeNodePrevious->routeIndexByRegex[ $partRegex ][ $route->id ] = true)
                    : ($routeNodePrevious->routeIndexByPart[ $part ][ $route->id ] = true);

                foreach ( $route->httpMethodIndex ?? [] as $httpMethod => $bool ) {
                    $routeNodePrevious->routeIndexByMethod[ $httpMethod ][ $route->id ] = true;
                }

            } else {
                if ($isPattern) {
                    $routeNode = $routeNodePrevious->childrenByRegex[ $partRegex ] ?? null;

                    if (null === $routeNode) {
                        $routeNode = $this->factory->newRouteNode();
                        $routeNode->part = $part;

                        $routeNodePrevious->childrenByRegex[ $partRegex ] = $routeNode;
                    }

                } else {
                    $routeNode = $routeNodePrevious->childrenByPart[ $part ] ?? null;

                    if (null === $routeNode) {
                        $routeNode = $this->factory->newRouteNode();
                        $routeNode->part = $part;

                        $routeNodePrevious->childrenByPart[ $part ] = $routeNode;
                    }
                }

                $routeNodePrevious = $routeNode;
            }
        }

        return $this;
    }

    protected function registerPattern(Pattern $pattern) // : static
    {
        $this->isRouterChanged = true;

        if (isset($this->patternDict[ $pattern->pattern ])) {
            throw new RuntimeException(
                'The `pattern` is already exists: ' . $pattern->pattern
            );
        }

        $this->patternCollection->registerPattern($pattern);

        return $this;
    }

    protected function registerMiddleware(GenericMiddleware $middleware) // : static
    {
        $this->isRouterChanged = true;

        if (! $this->registerAllowObjectsAndClosures) {
            if ($middleware->closure || $middleware->methodObject || $middleware->invokableObject) {
                throw new RuntimeException(
                    'This `middleware` should not be runtime object or \Closure: ' . Lib::php_dump($middleware)
                );
            }
        }

        $this->middlewareCollection->registerMiddleware($middleware);

        return $this;
    }

    protected function registerFallback(GenericFallback $fallback) // : static
    {
        $this->isRouterChanged = true;

        if (! $this->registerAllowObjectsAndClosures) {
            if ($fallback->closure || $fallback->methodObject || $fallback->invokableObject) {
                throw new RuntimeException(
                    'This `fallback` should not be runtime object or \Closure: ' . Lib::php_dump($fallback)
                );
            }
        }

        $this->fallbackCollection->registerFallback($fallback);

        return $this;
    }


    protected function compileRoute(RouteBlueprint $routeBlueprint) : Route
    {
        if (null === ($path = $routeBlueprint->path)) {
            throw new RuntimeException(
                'Missing `path` in route: ' . Lib::php_dump($routeBlueprint)
            );
        }

        if (null === $routeBlueprint->action) {
            throw new RuntimeException(
                'Missing `action` in route: ' . Lib::php_dump($routeBlueprint)
            );
        }

        if (null === $routeBlueprint->httpMethodIndex) {
            throw new RuntimeException(
                'Missing `method` in route: ' . Lib::php_dump($routeBlueprint)
            );
        }

        $pathValue = $path->getValue();

        if (! $this->compileTrailingSlashMode) {
            if ('/' === $pathValue[ strlen($pathValue) - 1 ]) {
                throw new RuntimeException(
                    'The `path` must not end with `/` sign: ' . $pathValue
                );
            }
        }

        $pathRegex = $this->compilePathRegex($pathValue, $attributesIndex);

        $route = $this->factory->newRoute();

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
            foreach ( $routeBlueprint->middlewareDict ?? [] as $middleware ) {
                $this->middlewareOnPath($pathValue, $middleware);
            }
        }

        if ($routeBlueprint->tagIndex || $routeBlueprint->fallbackDict) {
            foreach ( $routeBlueprint->fallbackDict ?? [] as $fallback ) {
                $this->fallbackOnPath($pathValue, $fallback);
            }
        }

        return $route;
    }

    protected function compilePathRegex(string $path, array &$attributesIndex = null) : string
    {
        $attributesIndex = null;

        $patternDict = $this->patternCollection->patternDict ?? [];

        $regex = ''
            . preg_quote(static::PATTERN_ENCLOSURE[ 0 ], '/')
            . '[^' . preg_quote(static::PATTERN_ENCLOSURE[ 1 ], '/') . ']+'
            . preg_quote(static::PATTERN_ENCLOSURE[ 1 ], '/');

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

        if (null === Lib::filter_regex('/^' . $pathRegex . '$/')) {
            throw new RuntimeException(
                'The output regex is not valid: ' . $pathRegex
            );
        }

        $attributesIndex = $attributesIndex ?? [];

        return $pathRegex;
    }
}
