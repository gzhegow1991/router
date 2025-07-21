<?php

namespace Gzhegow\Router\Core\Route;

use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
use Gzhegow\Router\Core\Route\Struct\RouteMethod;
use Gzhegow\Router\Core\Handler\Action\RouterGenericHandlerAction;
use Gzhegow\Router\Core\Handler\Fallback\RouterGenericHandlerFallback;
use Gzhegow\Router\Core\Handler\Middleware\RouterGenericHandlerMiddleware;


class RouteBlueprint
{
    /**
     * @var RoutePath
     */
    public $path;
    /**
     * @var RouterGenericHandlerAction
     */
    public $action;
    /**
     * @var RouteName
     */
    public $name;

    /**
     * @var array<string, bool>
     */
    public $methodIndex = [];
    /**
     * @var array<string, bool>
     */
    public $tagIndex = [];

    /**
     * @var array<string, RouterGenericHandlerMiddleware>
     */
    public $middlewareDict = [];
    /**
     * @var array<string, RouterGenericHandlerFallback>
     */
    public $fallbackDict = [];


    /**
     * @return static
     */
    public function reset()
    {
        $this->path = null;
        $this->action = null;
        $this->name = null;

        $this->methodIndex = [];
        $this->tagIndex = [];

        $this->middlewareDict = [];
        $this->fallbackDict = [];

        return $this;
    }


    /**
     * @return static
     */
    public function action($action)
    {
        $routeGenericAction = RouterGenericHandlerAction::from($action)->orThrow();

        $this->action = $routeGenericAction;

        return $this;
    }

    /**
     * @return static
     */
    public function resetAction()
    {
        $this->action = null;

        return $this;
    }


    /**
     * @return static
     */
    public function path($routePath)
    {
        $routePathObject = RoutePath::from($routePath)->orThrow();

        $this->path = $routePathObject;

        return $this;
    }

    /**
     * @return static
     */
    public function name($name)
    {
        $routeNameObject = RouteName::from($name)->orThrow();

        $this->name = $routeNameObject;

        return $this;
    }


    /**
     * @return static
     */
    public function setHttpMethods(array $httpMethods)
    {
        $this->methodIndex = [];

        $this->httpMethods($httpMethods);

        return $this;
    }

    /**
     * @return static
     */
    public function httpMethods(array $httpMethods)
    {
        foreach ( $httpMethods as $httpMethod ) {
            $this->httpMethod($httpMethod);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function httpMethod($routeMethod)
    {
        $routeMethodObject = RouteMethod::from($routeMethod)->orThrow();

        $this->methodIndex[ $routeMethodObject->getValue() ] = true;

        return $this;
    }


    /**
     * @return static
     */
    public function setTags(array $tags)
    {
        $this->tagIndex = [];

        $this->tags($tags);

        return $this;
    }

    /**
     * @return static
     */
    public function tags(array $tags)
    {
        foreach ( $tags as $tag ) {
            $this->tag($tag);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function tag($tag)
    {
        $routeTagObject = RouteTag::from($tag)->orThrow();

        $this->tagIndex[ $routeTagObject->getValue() ] = true;

        return $this;
    }


    /**
     * @return static
     */
    public function setMiddlewares(array $middlewares)
    {
        $this->middlewareDict = [];

        $this->middlewares($middlewares);

        return $this;
    }

    /**
     * @return static
     */
    public function middlewares(array $middlewares)
    {
        foreach ( $middlewares as $middleware ) {
            $this->middleware($middleware);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function middleware($middleware)
    {
        $routerGenericMiddleware = RouterGenericHandlerMiddleware::from($middleware)->orThrow();

        $this->middlewareDict[ $routerGenericMiddleware->getKey() ] = $routerGenericMiddleware;

        return $this;
    }


    /**
     * @return static
     */
    public function setFallbacks(array $fallbacks)
    {
        $this->fallbackDict = [];

        $this->fallbacks($fallbacks);

        return $this;
    }

    /**
     * @return static
     */
    public function fallbacks(array $fallbacks)
    {
        foreach ( $fallbacks as $fallback ) {
            $this->fallback($fallback);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function fallback($fallback)
    {
        $routerGenericFallback = RouterGenericHandlerFallback::from($fallback)->orThrow();

        $this->fallbackDict[ $routerGenericFallback->getKey() ] = $routerGenericFallback;

        return $this;
    }
}
