<?php

namespace Gzhegow\Router\Route;

use Gzhegow\Router\RouterInterface;


/**
 * @method
 */
class RouteGroup
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var RouteBlueprint
     */
    protected $routeBlueprint;

    /**
     * @var RouteBlueprint[]
     */
    public $routeList = [];


    public function __construct(
        RouterInterface $router,
        //
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


    public function httpMethodList($httpMethods) // : static
    {
        $this->routeBlueprint->httpMethodList($httpMethods);

        return $this;
    }

    public function httpMethod($httpMethods) // : static
    {
        $this->routeBlueprint->httpMethod($httpMethods);

        return $this;
    }


    public function tagList($tags) // : static
    {
        $this->routeBlueprint->tagList($tags);

        return $this;
    }

    public function tag($tags) // : static
    {
        $this->routeBlueprint->tag($tags);

        return $this;
    }


    public function middlewareList($middlewares) // : static
    {
        $this->routeBlueprint->middlewareList($middlewares);

        return $this;
    }

    public function middleware($middlewares) // : static
    {
        $this->routeBlueprint->middleware($middlewares);

        return $this;
    }


    public function fallbackList($fallbacks) // : static
    {
        $this->routeBlueprint->fallbackList($fallbacks);

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
