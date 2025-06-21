<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Struct\Tag;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


class RouterMiddlewareCollection implements \Serializable
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


    public function __serialize() : array
    {
        $vars = get_object_vars($this);

        return array_filter($vars);
    }

    public function __unserialize(array $data) : void
    {
        foreach ( $data as $key => $val ) {
            $this->{$key} = $val;
        }
    }

    public function serialize()
    {
        $array = $this->__serialize();

        return serialize($array);
    }

    public function unserialize($data)
    {
        $array = unserialize($data);

        $this->__unserialize($array);
    }


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
