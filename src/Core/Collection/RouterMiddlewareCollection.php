<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


/**
 * @property GenericHandlerMiddleware[]      $middlewareList
 *
 * @property array<string, array<int, bool>> $middlewareIndexByRouteId
 * @property array<string, array<int, bool>> $middlewareIndexByRoutePath
 * @property array<string, array<int, bool>> $middlewareIndexByRouteTag
 */
class RouterMiddlewareCollection implements \Serializable
{
    /**
     * @var int
     */
    protected $middlewareLastId = 0;

    /**
     * @var GenericHandlerMiddleware[]
     */
    protected $middlewareList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    protected $middlewareIndexByRouteId = [];
    /**
     * @var array<string, array<int, bool>>
     */
    protected $middlewareIndexByRoutePath = [];
    /**
     * @var array<string, array<int, bool>>
     */
    protected $middlewareIndexByRouteTag = [];

    /**
     * @var array<string, int>
     */
    protected $middlewareMapKeyToId;


    public function __get($name)
    {
        switch ( $name ):
            case 'middlewareList':
                return $this->middlewareList;


            case 'middlewareIndexByRouteId':
                return $this->middlewareIndexByRouteId;

            case 'middlewareIndexByRoutePath':
                return $this->middlewareIndexByRoutePath;

            case 'middlewareIndexByRouteTag':
                return $this->middlewareIndexByRouteTag;

            default:
                throw new RuntimeException(
                    [ 'The property is missing: ' . $name ]
                );

        endswitch;
    }


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


    /**
     * @return GenericHandlerMiddleware[]
     */
    public function getMiddlewareList() : array
    {
        return $this->middlewareList;
    }


    public function hasMiddleware(int $id, ?GenericHandlerMiddleware &$refMiddleware = null) : bool
    {
        $refMiddleware = null;

        if (isset($this->middlewareList[ $id ])) {
            $refMiddleware = $this->middlewareList[ $id ];

            return true;
        }

        return false;
    }

    public function getMiddleware(int $id) : GenericHandlerMiddleware
    {
        return $this->middlewareList[ $id ];
    }


    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int
    {
        $key = $middleware->getKey();

        $id = $this->middlewareMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = ++$this->middlewareLastId;

            $this->middlewareList[ $id ] = $middleware;

            $this->middlewareMapKeyToId[ $key ] = $id;
        }

        return $id;
    }


    public function addRouteIdMiddleware(int $routeId, GenericHandlerMiddleware $middleware) : int
    {
        $id = $this->registerMiddleware($middleware);

        $this->middlewareIndexByRouteId[ $routeId ][ $id ] = true;

        return $id;
    }

    public function addRoutePathMiddleware(RoutePath $routePath, GenericHandlerMiddleware $middleware) : int
    {
        $id = $this->registerMiddleware($middleware);

        $this->middlewareIndexByRoutePath[ $routePath->getValue() ][ $id ] = true;

        return $id;
    }

    public function addRouteTagMiddleware(RouteTag $routeTag, GenericHandlerMiddleware $middleware) : int
    {
        $id = $this->registerMiddleware($middleware);

        $this->middlewareIndexByRouteTag[ $routeTag->getValue() ][ $id ] = true;

        return $id;
    }
}
