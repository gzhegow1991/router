<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Cache\RouterCacheConfig;
use Gzhegow\Router\Cache\RouterCacheInterface;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Collection\MiddlewareCollection;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineProcessorInterface;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineProcessManagerInterface;


interface RouterFactoryInterface
{
    public function newRouter(
        RouterCacheInterface $routerCache,
        //
        PipelineFactoryInterface $pipelineFactory,
        PipelineProcessManagerInterface $pipelineProcessManager,
        //
        RouterConfig $config
    ) : RouterInterface;


    public function newRouterCache(RouterCacheConfig $config) : RouterCacheInterface;


    public function newPipelineFactory() : PipelineFactoryInterface;

    public function newPipelineProcessor(
        PipelineFactoryInterface $factory
    ) : PipelineProcessorInterface;

    public function newPipelineProcessManager(
        PipelineFactoryInterface $factory,
        PipelineProcessorInterface $processor
    ) : PipelineProcessManagerInterface;


    public function newRouteCollection() : RouteCollection;

    public function newPatternCollection() : PatternCollection;

    public function newMiddlewareCollection() : MiddlewareCollection;

    public function newFallbackCollection() : FallbackCollection;


    public function newRouterNode() : RouterNode;


    public function newRouteBlueprint(RouteBlueprint $from = null) : RouteBlueprint;

    public function newRouteGroup(RouteBlueprint $routeBlueprint = null) : RouteGroup;

    public function newRoute() : Route;
}
