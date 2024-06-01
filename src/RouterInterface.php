<?php
/**
 * @noinspection PhpUndefinedNamespaceInspection
 * @noinspection PhpUndefinedClassInspection
 */

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Contract\RouterMatchContract;
use Gzhegow\Router\Contract\RouterDispatchContract;


interface RouterInterface
{
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
    );

    /**
     * @param array{
     *     cacheMode: string|null,
     *     cacheAdapter: object|\Psr\Cache\CacheItemPoolInterface|null,
     *     cacheDirpath: string|null,
     *     cacheFilename: string|null,
     * }|null $settings
     */
    public function setCacheSettings(array $settings = null);


    public function cacheClear();

    public function cacheRemember($fn);


    /**
     * @param string[] $names
     *
     * @return Route[][]
     */
    public function matchAllByNames(array $names) : array;

    public function matchFirstByName(string $name, array $optional = [], array &$routes = null) : ?Route;


    /**
     * @param string[] $tags
     *
     * @return Route[][]
     */
    public function matchAllByTags(array $tags) : array;

    public function matchFirstByTag(string $tag, array $optional = [], array &$routes = null) : ?Route;


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatchContract $contract) : array;


    /**
     * @throws \Throwable
     */
    public function dispatch(RouterDispatchContract $contract, $input = null, $context = null);


    /**
     * @param Route|Route[]|string|string[] $routes
     *
     * @return string[]
     */
    public function urls($routes, array $attributes = []) : array;


    /**
     * @param string $pattern
     * @param string $regex
     */
    public function pattern($pattern, $regex);


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnPath($path, $middleware);

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $middleware
     */
    public function middlewareOnTag($tag, $middleware);


    /**
     * @param string                                    $path
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnPath($path, $fallback);

    /**
     * @param string                                    $tag
     * @param callable|object|array|class-string|string $fallback
     */
    public function fallbackOnTag($tag, $fallback);


    public function blueprint(RouteBlueprint $from = null) : RouteBlueprint;

    public function group(RouteBlueprint $from = null) : RouteGroup;


    /**
     * @param string                                    $path
     * @param string|string[]                           $httpMethods
     * @param callable|object|array|class-string|string $action
     */
    public function route($path, $httpMethods, $action, $name = null) : RouteBlueprint;

    public function routeAdd($pathOrBlueprint, ...$arguments);
}
