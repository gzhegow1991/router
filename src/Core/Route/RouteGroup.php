<?php

namespace Gzhegow\Router\Core\Route;

use Gzhegow\Router\Core\RouterFactoryInterface;


class RouteGroup
{
    /**
     * @var RouterFactoryInterface
     */
    protected $routerFactory;

    /**
     * @var static
     */
    protected $parent;

    /**
     * @var RouteBlueprint
     */
    protected $routeBlueprint;

    /**
     * @var RouteBlueprint[]
     */
    public $routeList = [];


    public function __construct(
        RouterFactoryInterface $routerFactory,
        //
        RouteBlueprint $routeBlueprint
    )
    {
        $this->routerFactory = $routerFactory;

        $this->routeBlueprint = $routeBlueprint;
    }


    public function getRouteBlueprint() : RouteBlueprint
    {
        return $this->routeBlueprint;
    }


    /**
     * @return static
     */
    public function reset()
    {
        $this->routeBlueprint->reset();

        $this->routeList = [];

        return $this;
    }


    public function group(RouteBlueprint $from = null) : RouteGroup
    {
        $routeBlueprint = $this->routerFactory->newRouteBlueprint($from);

        $routeGroup = $this->routerFactory->newRouteGroup($routeBlueprint);

        $routeGroup->parent = $this;

        return $routeGroup;
    }


    /**
     * @return RouteBlueprint[]
     */
    public function getRoutes() : array
    {
        return $this->routeList;
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
        $_httpMethods = $httpMethods ?? [ 'GET' ];
        $_httpMethods = (array) $_httpMethods;

        $blueprint = $this->blueprint(
            null,
            $path, $_httpMethods, $action, $name, $tags
        );

        $this->routeList[] = $blueprint;

        return $blueprint;
    }

    /**
     * @return static
     */
    public function addRoute(RouteBlueprint $routeBlueprint)
    {
        $this->routeList[] = $routeBlueprint;

        return $this;
    }


    public function newBlueprint(RouteBlueprint $from = null) : RouteBlueprint
    {
        $routeBlueprint = $this->routerFactory->newRouteBlueprint($from);

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
        $path = null, $httpMethods = null, $action = null,
        $name = null, $tags = null
    ) : RouteBlueprint
    {
        $blueprint = $this->newBlueprint($from);

        if (null !== $path) {
            $blueprint->path($path);
        }

        if (null !== $httpMethods) {
            $blueprint->setHttpMethods($httpMethods);
        }

        if (null !== $action) {
            $blueprint->action($action);
        }

        if (null !== $name) {
            $blueprint->name($name);
        }

        if (null !== $tags) {
            $blueprint->setTags((array) $tags);
        }

        return $blueprint;
    }


    /**
     * @return static
     */
    public function path($path)
    {
        $this->routeBlueprint->path($path);

        return $this;
    }

    /**
     * @return static
     */
    public function name($name)
    {
        $this->routeBlueprint->name($name);

        return $this;
    }


    /**
     * @return static
     */
    public function setHttpMethods(array $httpMethods)
    {
        $this->routeBlueprint->setHttpMethods($httpMethods);

        return $this;
    }

    /**
     * @return static
     */
    public function httpMethods(array $httpMethods)
    {
        $this->routeBlueprint->httpMethods($httpMethods);

        return $this;
    }

    /**
     * @return static
     */
    public function httpMethod($httpMethod)
    {
        $this->routeBlueprint->httpMethod($httpMethod);

        return $this;
    }


    /**
     * @return static
     */
    public function setTags(array $tags)
    {
        $this->routeBlueprint->setTags($tags);

        return $this;
    }

    /**
     * @return static
     */
    public function tags(array $tags)
    {
        $this->routeBlueprint->tags($tags);

        return $this;
    }

    /**
     * @return static
     */
    public function tag($tag)
    {
        $this->routeBlueprint->tag($tag);

        return $this;
    }


    /**
     * @return static
     */
    public function setMiddlewares(array $middlewares)
    {
        $this->routeBlueprint->setMiddlewares($middlewares);

        return $this;
    }

    /**
     * @return static
     */
    public function middlewares(array $middlewares)
    {
        $this->routeBlueprint->middlewares($middlewares);

        return $this;
    }

    /**
     * @return static
     */
    public function middleware($middleware)
    {
        $this->routeBlueprint->middleware($middleware);

        return $this;
    }


    /**
     * @return static
     */
    public function setFallbacks(array $fallbacks)
    {
        $this->routeBlueprint->setFallbacks($fallbacks);

        return $this;
    }

    /**
     * @return static
     */
    public function fallbacks(array $fallbacks)
    {
        $this->routeBlueprint->fallbacks($fallbacks);

        return $this;
    }

    /**
     * @return static
     */
    public function fallback($fallback)
    {
        $this->routeBlueprint->fallback($fallback);

        return $this;
    }


    /**
     * @param callable $fn
     *
     * @return static
     */
    public function register($fn)
    {
        $fn($this);

        if (null === $this->parent) {
            return $this;
        }

        foreach ( $this->routeList as $routeBlueprint ) {
            $path = $this->routeBlueprint->path . $routeBlueprint->path;
            $name = $this->routeBlueprint->name . $routeBlueprint->name;

            $tagsIndex = array_replace(
                $routeBlueprint->tagIndex,
                $this->routeBlueprint->tagIndex
            );
            $tagsList = array_keys($tagsIndex);

            $middlewaresDict = array_replace(
                $routeBlueprint->middlewareDict,
                $this->routeBlueprint->middlewareDict
            );

            $fallbacksDict = array_replace(
                $routeBlueprint->fallbackDict,
                $this->routeBlueprint->fallbackDict
            );

            if ('' !== $path) {
                $routeBlueprint->path($path);
            }

            if ('' !== $name) {
                $routeBlueprint->name($name);
            }

            $routeBlueprint->setTags($tagsList);
            $routeBlueprint->setMiddlewares($middlewaresDict);
            $routeBlueprint->setFallbacks($fallbacksDict);

            $this->parent->addRoute($routeBlueprint);
        }

        $this->routeList = [];

        return $this;
    }
}
