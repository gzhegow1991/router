<?php

namespace Gzhegow\Router\Core\Route;

use Gzhegow\Router\Core\Route\Struct\Tag;
use Gzhegow\Router\Core\Route\Struct\Name;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Core\Route\Struct\HttpMethod;
use Gzhegow\Router\Core\Handler\Action\GenericHandlerAction;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


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
    public function reset()
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
    public function action($action)
    {
        $actionObject = GenericHandlerAction::from($action);

        $this->action = $actionObject;

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
    public function path($path)
    {
        $pathObject = Path::from($path);

        $this->path = $pathObject;

        return $this;
    }

    /**
     * @return static
     */
    public function name($name)
    {
        $nameObject = Name::from($name);

        $this->name = $nameObject;

        return $this;
    }


    /**
     * @return static
     */
    public function setHttpMethods(array $httpMethods)
    {
        $this->httpMethodIndex = [];

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
    public function httpMethod($httpMethod)
    {
        $httpMethodObject = HttpMethod::from($httpMethod);

        $this->httpMethodIndex[ $httpMethodObject->getValue() ] = true;

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
        $_tag = Tag::from($tag);

        $this->tagIndex[ $_tag->getValue() ] = true;

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
        $_middleware = GenericHandlerMiddleware::from($middleware);

        $this->middlewareDict[ $_middleware->getKey() ] = $_middleware;

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
        $_fallback = GenericHandlerFallback::from($fallback);

        $this->fallbackDict[ $_fallback->getKey() ] = $_fallback;

        return $this;
    }
}
