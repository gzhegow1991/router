<?php

namespace Gzhegow\Router\Route;

use Gzhegow\Router\Route\Struct\Tag;
use Gzhegow\Router\Route\Struct\Name;
use Gzhegow\Router\Route\Struct\Path;
use Gzhegow\Router\Route\Struct\HttpMethod;
use Gzhegow\Router\Handler\Action\GenericAction;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;
use function Gzhegow\Router\_array_string_index;


class RouteBlueprint
{
    /**
     * @var Path
     */
    public $path;
    /**
     * @var GenericAction
     */
    public $action;
    /**
     * @var Name
     */
    public $name;

    /**
     * @var array<string, bool>
     */
    public $httpMethodIndex; // = [];
    /**
     * @var array<string, bool>
     */
    public $tagIndex; // = [];

    /**
     * @var array<string, GenericMiddleware>
     */
    public $middlewareDict; // = [];
    /**
     * @var array<string, GenericFallback>
     */
    public $fallbackDict; // = [];


    public function path($path) // : static
    {
        $pathObject = Path::from($path);

        $this->path = $pathObject;

        return $this;
    }

    public function action($action) // : static
    {
        $actionObject = GenericAction::from($action);

        $this->action = $actionObject;

        return $this;
    }

    public function name($name) // : static
    {
        $nameObject = Name::from($name);

        $this->name = $nameObject;

        return $this;
    }


    public function httpMethods($httpMethods) // : static
    {
        $httpMethods = $httpMethods ?? [];
        $httpMethods = (array) $httpMethods;

        $index = [];
        if ($httpMethods) {
            $index = _array_string_index($httpMethods);
        }

        $httpMethodIndex = [];

        foreach ( $index as $httpMethod => $bool ) {
            $httpMethodObject = HttpMethod::from($httpMethod);

            $httpMethodIndex[ $httpMethodObject->getValue() ] = true;
        }

        $this->httpMethodIndex = $httpMethodIndex;

        return $this;
    }

    public function httpMethod($httpMethods) // : static
    {
        $httpMethods = $httpMethods ?? [];
        $httpMethods = (array) $httpMethods;

        if (! $httpMethods) {
            return $this;
        }

        $index = _array_string_index($this->httpMethodIndex ?? [], $httpMethods);

        $httpMethodIndex = [];

        foreach ( $index as $httpMethod => $bool ) {
            $httpMethodObject = HttpMethod::from($httpMethod);

            $httpMethodIndex[ $httpMethodObject->getValue() ] = true;
        }

        $this->httpMethodIndex = $httpMethodIndex;

        return $this;
    }


    public function tags($tags) // : static
    {
        $tags = $tags ?? [];
        $tags = (array) $tags;

        $index = [];
        if ($tags) {
            $index = _array_string_index($tags);
        }

        $tagIndex = [];

        foreach ( $index as $httpMethod => $bool ) {
            $tagObject = Tag::from($httpMethod);

            $tagIndex[ $tagObject->getValue() ] = true;
        }

        $this->tagIndex = $tagIndex;

        return $this;
    }

    public function tag($tags) // : static
    {
        $tags = $tags ?? [];
        $tags = (array) $tags;

        if (! $tags) {
            return $this;
        }

        $index = _array_string_index($this->tagIndex ?? [], $tags);

        $tagIndex = [];
        foreach ( $index as $httpMethod => $bool ) {
            $tagObject = Tag::from($httpMethod);

            $tagIndex[ $tagObject->getValue() ] = true;
        }

        $this->tagIndex = $tagIndex;

        return $this;
    }


    public function middlewares($middlewares) // : static
    {
        $middlewares = $middlewares ?? [];
        $middlewares = (array) $middlewares;

        $middlewareDict = [];

        foreach ( $middlewares as $middleware ) {
            $middlewareObject = GenericMiddleware::from($middleware);

            $middlewareDict[ $middlewareObject->getKey() ] = $middlewareObject;
        }

        $this->middlewareDict = $middlewareDict;

        return $this;
    }

    public function middleware($middlewares) // : static
    {
        $middlewares = $middlewares ?? [];
        $middlewares = (array) $middlewares;

        if (! $middlewares) {
            return $this;
        }

        foreach ( $middlewares as $middleware ) {
            $middlewareObject = GenericMiddleware::from($middleware);

            $this->middlewareDict[ $middlewareObject->getKey() ] = $middlewareObject;
        }

        return $this;
    }


    public function fallbacks($fallbacks) // : static
    {
        $fallbacks = $fallbacks ?? [];
        $fallbacks = (array) $fallbacks;

        $fallbackDict = [];

        foreach ( $fallbacks as $middleware ) {
            $fallbackObject = GenericFallback::from($middleware);

            $fallbackDict[ $fallbackObject->getKey() ] = $fallbackObject;
        }

        $this->fallbackDict = $fallbackDict;

        return $this;
    }

    public function fallback($fallbacks) // : static
    {
        $fallbacks = $fallbacks ?? [];
        $fallbacks = (array) $fallbacks;

        if (! $fallbacks) {
            return $this;
        }

        foreach ( $fallbacks as $fallback ) {
            $fallbackObject = GenericFallback::from($fallback);

            $this->fallbackDict[ $fallbackObject->getKey() ] = $fallbackObject;
        }

        return $this;
    }
}
