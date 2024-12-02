<?php

namespace Gzhegow\Router\Route;

use Gzhegow\Router\Route\Struct\Tag;
use Gzhegow\Router\Route\Struct\Name;
use Gzhegow\Router\Route\Struct\Path;
use Gzhegow\Router\Route\Struct\HttpMethod;
use Gzhegow\Router\Handler\Action\GenericHandlerAction;
use Gzhegow\Router\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Handler\Middleware\GenericHandlerMiddleware;


class RouteBlueprint
{
    /**
     * @var Path
     */
    public $path;
    /**
     * @var GenericHandlerAction
     */
    public $action;
    /**
     * @var Name
     */
    public $name;

    /**
     * @var array<string, bool>
     */
    public $httpMethodIndex = [];
    /**
     * @var array<string, bool>
     */
    public $tagIndex = [];

    /**
     * @var array<string, GenericHandlerMiddleware>
     */
    public $middlewareDict = [];
    /**
     * @var array<string, GenericHandlerFallback>
     */
    public $fallbackDict = [];


    /**
     * @return static
     */
    public function reset() // : static
    {
        $this->path = null;
        $this->action = null;
        $this->name = null;

        $this->httpMethodIndex = [];
        $this->tagIndex = [];

        $this->middlewareDict = [];
        $this->fallbackDict = [];

        return $this;
    }


    /**
     * @return static
     */
    public function action($action) // : static
    {
        $actionObject = GenericHandlerAction::from($action);

        $this->action = $actionObject;

        return $this;
    }

    /**
     * @return static
     */
    public function resetAction() // : static
    {
        $this->action = null;

        return $this;
    }


    /**
     * @return static
     */
    public function path($path) // : static
    {
        $pathObject = Path::from($path);

        $this->path = $pathObject;

        return $this;
    }

    /**
     * @return static
     */
    public function name($name) // : static
    {
        $nameObject = Name::from($name);

        $this->name = $nameObject;

        return $this;
    }


    /**
     * @return static
     */
    public function setHttpMethods(array $httpMethods) // : static
    {
        $this->httpMethodIndex = [];

        $this->httpMethods($httpMethods);

        return $this;
    }

    /**
     * @return static
     */
    public function httpMethods(array $httpMethods) // : static
    {
        foreach ( $httpMethods as $httpMethod ) {
            $this->httpMethod($httpMethod);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function httpMethod($httpMethod) // : static
    {
        $_httpMethod = HttpMethod::from($httpMethod);

        $this->httpMethodIndex[ $_httpMethod->getValue() ] = true;

        return $this;
    }


    /**
     * @return static
     */
    public function setTags(array $tags) // : static
    {
        $this->tagIndex = [];

        $this->tags($tags);

        return $this;
    }

    /**
     * @return static
     */
    public function tags(array $tags) // : static
    {
        foreach ( $tags as $tag ) {
            $this->tag($tag);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function tag($tag) // : static
    {
        $_tag = Tag::from($tag);

        $this->tagIndex[ $_tag->getValue() ] = true;

        return $this;
    }


    /**
     * @return static
     */
    public function setMiddlewares(array $middlewares) // : static
    {
        $this->middlewareDict = [];

        $this->middlewares($middlewares);

        return $this;
    }

    /**
     * @return static
     */
    public function middlewares(array $middlewares) // : static
    {
        foreach ( $middlewares as $middleware ) {
            $this->middleware($middleware);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function middleware($middleware) // : static
    {
        $_middleware = GenericHandlerMiddleware::from($middleware);

        $this->middlewareDict[ $_middleware->getKey() ] = $_middleware;

        return $this;
    }


    /**
     * @return static
     */
    public function setFallbacks(array $fallbacks) // : static
    {
        $this->fallbackDict = [];

        $this->fallbacks($fallbacks);

        return $this;
    }

    /**
     * @return static
     */
    public function fallbacks(array $fallbacks) // : static
    {
        foreach ( $fallbacks as $fallback ) {
            $this->fallback($fallback);
        }

        return $this;
    }

    /**
     * @return static
     */
    public function fallback($fallback) // : static
    {
        $_fallback = GenericHandlerFallback::from($fallback);

        $this->fallbackDict[ $_fallback->getKey() ] = $_fallback;

        return $this;
    }
}
