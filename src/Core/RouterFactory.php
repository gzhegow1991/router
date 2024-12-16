<?php

namespace Gzhegow\Router\Core;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Node\Node;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Collection\RouteCollection;
use Gzhegow\Router\Core\Collection\PatternCollection;
use Gzhegow\Router\Core\Collection\FallbackCollection;
use Gzhegow\Router\Core\Collection\MiddlewareCollection;


class RouterFactory implements RouterFactoryInterface
{
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


    public function newRouterNode() : Node
    {
        return new Node();
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
