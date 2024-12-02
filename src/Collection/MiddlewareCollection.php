<?php

namespace Gzhegow\Router\Collection;

use Gzhegow\Router\Route\Struct\Tag;
use Gzhegow\Router\Route\Struct\Path;
use Gzhegow\Router\Handler\Middleware\GenericHandlerMiddleware;


class MiddlewareCollection
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var GenericHandlerMiddleware[]
     */
    public $middlewareList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    public $middlewareIndexByPath = [];
    /**
     * @var array<string, array<int, bool>>
     */
    public $middlewareIndexByTag = [];

    /**
     * @var array<string, int>
     */
    public $middlewareMapKeyToId;


    public function getMiddleware(int $id) : GenericHandlerMiddleware
    {
        return $this->middlewareList[ $id ];
    }


    public function addPathMiddleware(Path $path, GenericHandlerMiddleware $middleware) : int
    {
        $id = $this->registerMiddleware($middleware);

        $this->middlewareIndexByPath[ $path->getValue() ][ $id ] = true;

        return $id;
    }

    public function addTagMiddleware(Tag $tag, GenericHandlerMiddleware $middleware) : int
    {
        $id = $this->registerMiddleware($middleware);

        $this->middlewareIndexByTag[ $tag->getValue() ][ $id ] = true;

        return $id;
    }


    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int
    {
        $key = $middleware->getKey();

        $id = $this->middlewareMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = ++$this->id;

            $this->middlewareList[ $id ] = $middleware;

            $this->middlewareMapKeyToId[ $key ] = $id;
        }

        return $id;
    }
}
