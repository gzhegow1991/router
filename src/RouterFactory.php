<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\RouterNode;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Core\Collection\RouterPatternCollection;
use Gzhegow\Router\Core\Collection\RouterFallbackCollection;
use Gzhegow\Router\Core\Collection\RouterMiddlewareCollection;


class RouterFactory implements RouterFactoryInterface
{
    public function newRouteCollection() : RouterRouteCollection
    {
        return new RouterRouteCollection();
    }

    public function newPatternCollection() : RouterPatternCollection
    {
        return new RouterPatternCollection();
    }

    public function newMiddlewareCollection() : RouterMiddlewareCollection
    {
        return new RouterMiddlewareCollection();
    }

    public function newFallbackCollection() : RouterFallbackCollection
    {
        return new RouterFallbackCollection();
    }


    public function newRouterNode() : RouterNode
    {
        return new RouterNode();
    }


    public function newRouteBlueprint(?RouteBlueprint $from = null) : RouteBlueprint
    {
        if ($from) {
            $instance = clone $from;

        } else {
            $instance = new RouteBlueprint();
        }

        $instance->resetAction();

        return $instance;
    }

    public function newRouteGroup(?RouteBlueprint $routeBlueprint = null) : RouteGroup
    {
        $routeBlueprint = $this->newRouteBlueprint($routeBlueprint);

        return new RouteGroup($this, $routeBlueprint);
    }

    public function newRoute() : Route
    {
        return new Route();
    }
}
