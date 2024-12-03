<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Cache\RouterCache;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Cache\RouterCacheInterface;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Collection\MiddlewareCollection;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineFactory;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineFactoryInterface;


class RouterFactory implements RouterFactoryInterface
{
    public function newRouter(
        PipelineFactoryInterface $pipelineFactory = null,
        //
        RouterCacheInterface $routerCache = null
    ) : RouterInterface
    {
        $pipelineFactory = $pipelineFactory ?? new PipelineFactory();

        $routerCache = $routerCache ?? $this->newRouterCache();

        return new Router(
            $this,
            $pipelineFactory,
            //
            $routerCache
        );
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


    public function newRouteBlueprint(RouteBlueprint $from = null) : RouteBlueprint
    {
        if ($from) {
            $instance = clone $from;

        } else {
            $instance = new RouteBlueprint();
        }

        $instance->resetAction();

        return $instance;
    }

    public function newRouteGroup(RouteBlueprint $routeBlueprint = null) : RouteGroup
    {
        $routeBlueprint = $this->newRouteBlueprint($routeBlueprint);

        return new RouteGroup($this, $routeBlueprint);
    }

    public function newRoute() : Route
    {
        return new Route();
    }
}
