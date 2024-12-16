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


interface RouterFactoryInterface
{
    public function newRouteCollection() : RouteCollection;

    public function newPatternCollection() : PatternCollection;

    public function newMiddlewareCollection() : MiddlewareCollection;

    public function newFallbackCollection() : FallbackCollection;


    public function newRouterNode() : Node;


    public function newRouteBlueprint(RouteBlueprint $from = null) : RouteBlueprint;

    public function newRouteGroup(RouteBlueprint $routeBlueprint = null) : RouteGroup;

    public function newRoute() : Route;
}
