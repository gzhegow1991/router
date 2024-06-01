<?php

namespace Gzhegow\Router\Route;

use Gzhegow\Router\Router;


/**
 * @method
 */
class RouteGroup
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RouteBlueprint
     */
    public $routeBlueprint;
    /**
     * @var RouteBlueprint[]
     */
    public $routeList = [];


    public function __construct(
        Router $router,
        RouteBlueprint $routeBlueprint
    )
    {
        $this->router = $router;
        $this->routeBlueprint = $routeBlueprint;
    }


    public function getRouteBlueprint() : RouteBlueprint
    {
        return $this->routeBlueprint;
    }


    /**
     * @return RouteBlueprint[]
     */
    public function getRouteList() : array
    {
        return $this->routeList ?? [];
    }

    public function addRoute(RouteBlueprint $routeBlueprint) // : static
    {
        $this->routeList[] = $routeBlueprint;

        return $this;
    }


    public function httpMethods($httpMethods) // : static
    {
        $this->routeBlueprint->httpMethods($httpMethods);

        return $this;
    }

    public function httpMethod($httpMethods) // : static
    {
        $this->routeBlueprint->httpMethod($httpMethods);

        return $this;
    }


    public function tags($tags) // : static
    {
        $this->routeBlueprint->tags($tags);

        return $this;
    }

    public function tag($tags) // : static
    {
        $this->routeBlueprint->tag($tags);

        return $this;
    }


    public function middlewares($middlewares) // : static
    {
        $this->routeBlueprint->middlewares($middlewares);

        return $this;
    }

    public function middleware($middlewares) // : static
    {
        $this->routeBlueprint->middleware($middlewares);

        return $this;
    }


    public function fallbacks($fallbacks) // : static
    {
        $this->routeBlueprint->fallbacks($fallbacks);

        return $this;
    }

    public function fallback($fallbacks) // : static
    {
        $this->routeBlueprint->fallback($fallbacks);

        return $this;
    }


    public function register($fn) // : static
    {
        $fn($this->router);

        (function (RouteGroup $routeGroup) {
            $this->{'registerRouteGroup'}($routeGroup);
        })->call($this->router, $this);

        return $this;
    }
}
