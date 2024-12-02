<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Cache\RouterCacheInterface;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Collection\MiddlewareCollection;


interface RouterFactoryInterface
{
    public function newRouter(
        PipelineFactoryInterface $pipelineFactory = null,
        //
        RouterCacheInterface $routerCache = null
    ) : RouterInterface;


    public function newRouterCache() : RouterCacheInterface;


    public function newRouteCollection() : RouteCollection;

    public function newPatternCollection() : PatternCollection;

    public function newMiddlewareCollection() : MiddlewareCollection;

    public function newFallbackCollection() : FallbackCollection;


    public function newRouteNode() : RouterNode;


    public function newRouteBlueprint(RouteBlueprint $from = null) : RouteBlueprint;

    public function newRouteGroup(RouteBlueprint $routeBlueprint = null) : RouteGroup;

    public function newRoute() : Route;
}
