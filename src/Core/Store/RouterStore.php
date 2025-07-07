<?php

namespace Gzhegow\Router\Core\Store;

use Gzhegow\Router\RouterFactoryInterface;
use Gzhegow\Router\Exception\RuntimeException;


class RouterStore
{
    /**
     * @var RouterFactoryInterface
     */
    protected $factory;

    /**
     * @var RouterRouteCollection
     */
    public $routeCollection;
    /**
     * @var RouterPatternCollection
     */
    public $patternCollection;
    /**
     * @var RouterMiddlewareCollection
     */
    public $middlewareCollection;
    /**
     * @var RouterFallbackCollection
     */
    public $fallbackCollection;

    /**
     * @var RouterNode
     */
    public $rootRouterNode;


    public function __construct(RouterFactoryInterface $factory)
    {
        $this->factory = $factory;

        $this->routeCollection = $this->factory->newRouteCollection();
        $this->patternCollection = $this->factory->newPatternCollection();
        $this->middlewareCollection = $this->factory->newMiddlewareCollection();
        $this->fallbackCollection = $this->factory->newFallbackCollection();

        $routerNodeRoot = $this->factory->newRouterNode();
        $routerNodeRoot->part = '';
        $this->rootRouterNode = $routerNodeRoot;
    }


    public function __isset($name)
    {
        return false;
    }

    public function __get($name)
    {
        throw new RuntimeException(
            [ 'Property not found: ' . $name ]
        );
    }

    public function __set($name, $value)
    {
        throw new RuntimeException(
            [ 'Property not found: ' . $name ]
        );
    }

    public function __unset($name)
    {
        throw new RuntimeException(
            [ 'Property not found: ' . $name ]
        );
    }
}
