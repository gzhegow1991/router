<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Pipeline\Pipeline;
use Gzhegow\Router\Cache\RouterCache;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Collection\MiddlewareCollection;


class RouterFactory
{
    public function newRouter() : Router
    {
        $processor = $this->newRouterProcessor();
        $cache = $this->newRouterCache();

        return new Router(
            $this,
            $processor,
            $cache
        );
    }


    public function newRouterProcessor() : RouterProcessor
    {
        return new RouterProcessor($this);
    }

    public function newRouterCache() : RouterCache
    {
        return new RouterCache($this);
    }


    public function newRouteCollection() : RouteCollection
    {
        return new RouteCollection();
    }

    public function newPatternCollection() : PatternCollection
    {
        return new PatternCollection();
    }

    public function newMiddlewareCollection() : MiddlewareCollection
    {
        return new MiddlewareCollection();
    }

    public function newFallbackCollection() : FallbackCollection
    {
        return new FallbackCollection();
    }


    public function newRouteBlueprint() : RouteBlueprint
    {
        return new RouteBlueprint();
    }

    public function newRouteGroup(Router $router, RouteBlueprint $routeBlueprint) : RouteGroup
    {
        return new RouteGroup($router, $routeBlueprint);
    }

    public function newRoute() : Route
    {
        return new Route();
    }

    public function newRouteNode() : RouterNode
    {
        return new RouterNode();
    }


    public function newPipeline(RouterProcessor $processor) : Pipeline
    {
        return new Pipeline($processor);
    }


    /**
     * @template-covariant T of object
     *
     * @param class-string<T>|T $class
     *
     * @return T
     */
    public function newHandlerObject(string $class, array $parameters = []) : object
    {
        return new $class(...$parameters);
    }
}
