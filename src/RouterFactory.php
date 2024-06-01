<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Pipeline\Pipeline;
use Gzhegow\Router\Cache\RouterCache;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Cache\RouterCacheInterface;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Collection\MiddlewareCollection;


class RouterFactory implements RouterFactoryInterface
{
    public function newRouter() : RouterInterface
    {
        $processor = $this->newRouterProcessor();
        $cache = $this->newRouterCache();

        return new Router(
            $this,
            $processor,
            $cache
        );
    }


    public function newRouterProcessor() : RouterProcessorInterface
    {
        return new RouterProcessor($this);
    }

    public function newRouterCache() : RouterCacheInterface
    {
        return new RouterCache();
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


    public function newRouteNode() : RouterNode
    {
        return new RouterNode();
    }


    public function newRouteBlueprint() : RouteBlueprint
    {
        return new RouteBlueprint();
    }

    public function newRouteGroup(RouterInterface $router, RouteBlueprint $routeBlueprint) : RouteGroup
    {
        return new RouteGroup($router, $routeBlueprint);
    }

    public function newRoute() : Route
    {
        return new Route();
    }


    public function newPipeline(RouterProcessorInterface $processor) : Pipeline
    {
        return new Pipeline($processor);
    }
}
