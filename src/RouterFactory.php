<?php

namespace Gzhegow\Router;

use Gzhegow\Router\Route\Route;
use Gzhegow\Router\Node\RouterNode;
use Gzhegow\Router\Route\RouteGroup;
use Gzhegow\Router\Cache\RouterCache;
use Gzhegow\Router\Route\RouteBlueprint;
use Gzhegow\Router\Cache\RouterCacheConfig;
use Gzhegow\Router\Collection\RouteCollection;
use Gzhegow\Router\Cache\RouterCacheInterface;
use Gzhegow\Router\Collection\PatternCollection;
use Gzhegow\Router\Collection\FallbackCollection;
use Gzhegow\Router\Collection\MiddlewareCollection;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineFactory;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineProcessor;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineProcessManager;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineProcessorInterface;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineProcessManagerInterface;


class RouterFactory implements RouterFactoryInterface
{
    public function newRouter(
        RouterCacheInterface $routerCache,
        //
        PipelineFactoryInterface $pipelineFactory,
        PipelineProcessManagerInterface $pipelineProcessManager,
        //
        RouterConfig $config
    ) : RouterInterface
    {
        $router = new Router(
            $this,
            $routerCache,
            //
            $pipelineFactory,
            $pipelineProcessManager,
            //
            $config
        );

        return $router;
    }


    public function newRouterCache(RouterCacheConfig $config) : RouterCacheInterface
    {
        return new RouterCache($config);
    }


    public function newPipelineFactory() : PipelineFactoryInterface
    {
        return new PipelineFactory();
    }

    public function newPipelineProcessor(
        PipelineFactoryInterface $factory
    ) : PipelineProcessorInterface
    {
        return new PipelineProcessor(
            $factory
        );
    }

    public function newPipelineProcessManager(
        PipelineFactoryInterface $factory,
        PipelineProcessorInterface $processor
    ) : PipelineProcessManagerInterface
    {
        return new PipelineProcessManager(
            $factory,
            $processor
        );
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


    public function newRouterNode() : RouterNode
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
