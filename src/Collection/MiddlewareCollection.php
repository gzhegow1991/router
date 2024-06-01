<?php

namespace Gzhegow\Router\Collection;

use Gzhegow\Router\Route\Struct\Tag;
use Gzhegow\Router\Route\Struct\Path;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


class MiddlewareCollection
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var GenericMiddleware[]
     */
    public $middlewareList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    public $middlewareIndexByPath;
    /**
     * @var array<string, array<int, bool>>
     */
    public $middlewareIndexByTag;

    /**
     * @var array<string, int>
     */
    public $middlewareMapKeyToId;


    public function getMiddleware(int $id) : GenericMiddleware
    {
        return $this->middlewareList[ $id ];
    }


    public function addPathMiddleware(Path $path, GenericMiddleware $fallback) : int
    {
        $id = $this->registerMiddleware($fallback);

        $this->middlewareIndexByPath[ $path->getValue() ][ $id ] = true;

        return $id;
    }

    public function addTagMiddleware(Tag $tag, GenericMiddleware $fallback) : int
    {
        $id = $this->registerMiddleware($fallback);

        $this->middlewareIndexByTag[ $tag->getValue() ][ $id ] = true;

        return $id;
    }


    public function registerMiddleware(GenericMiddleware $middleware) : int
    {
        $key = $middleware->getKey();

        $id = $this->middlewareMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = $this->id++;

            $this->middlewareList[ $id ] = $middleware;

            $this->middlewareMapKeyToId[ $key ] = $id;
        }

        return $id;
    }
}
