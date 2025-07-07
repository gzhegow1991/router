<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Store\RouterNode;
use Gzhegow\Router\Core\Route\RouteGroup;
use Gzhegow\Router\Core\Store\RouterStore;
use Gzhegow\Router\Core\Route\RouteBlueprint;
use Gzhegow\Router\Core\Store\RouterRouteCollection;
use Gzhegow\Router\Core\Store\RouterPatternCollection;
use Gzhegow\Router\Core\Store\RouterFallbackCollection;
use Gzhegow\Router\Core\Store\RouterMiddlewareCollection;


interface RouterFactoryInterface
{
    public function newRouterStore() : RouterStore;


    public function newRouteCollection() : RouterRouteCollection;

    public function newPatternCollection() : RouterPatternCollection;

    public function newMiddlewareCollection() : RouterMiddlewareCollection;

    public function newFallbackCollection() : RouterFallbackCollection;


    public function newRouterNode() : RouterNode;


    public function newRouteBlueprint(?RouteBlueprint $from = null) : RouteBlueprint;

    public function newRouteGroup(?RouteBlueprint $routeBlueprint = null) : RouteGroup;

    public function newRoute() : Route;
}
