<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Pipeline\Pipeline;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Cache\RouterCacheInterface;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Collection\MiddlewareCollection;


interface RouterFactoryInterface
{
    public function newRouter() : RouterInterface;


    public function newRouterProcessor() : RouterProcessorInterface;

    public function newRouterCache() : RouterCacheInterface;


    public function newRouteCollection() : RouteCollection;

    public function newPatternCollection() : PatternCollection;

    public function newMiddlewareCollection() : MiddlewareCollection;

    public function newFallbackCollection() : FallbackCollection;


    public function newRouteNode() : RouterNode;


    public function newRouteBlueprint() : RouteBlueprint;

    public function newRouteGroup(RouterInterface $router, RouteBlueprint $routeBlueprint) : RouteGroup;

    public function newRoute() : Route;


    public function newPipeline(RouterProcessorInterface $processor) : Pipeline;
}
