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


interface RouterFactoryInterface
{
    public function newRouteCollection() : RouterRouteCollection;

    public function newPatternCollection() : RouterPatternCollection;

    public function newMiddlewareCollection() : RouterMiddlewareCollection;

    public function newFallbackCollection() : RouterFallbackCollection;


    public function newRouterNode() : RouterNode;


    public function newRouteBlueprint(RouteBlueprint $from = null) : RouteBlueprint;

    public function newRouteGroup(RouteBlueprint $routeBlueprint = null) : RouteGroup;

    public function newRoute() : Route;
}
