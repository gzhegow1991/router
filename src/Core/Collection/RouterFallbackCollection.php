<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RoutePath;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Core\Handler\Fallback\RouterGenericHandlerFallback;


/**
 * @property RouterGenericHandlerFallback[]  $fallbackList
 *
 * @property array<string, array<int, bool>> $fallbackIndexByRouteId
 * @property array<string, array<int, bool>> $fallbackIndexByRoutePath
 * @property array<string, array<int, bool>> $fallbackIndexByRouteTag
 */
class RouterFallbackCollection implements \Serializable
{
    /**
     * @var int
     */
    protected $fallbackLastId = 0;

    /**
     * @var RouterGenericHandlerFallback[]
     */
    protected $fallbackList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    protected $fallbackIndexByRouteId = [];
    /**
     * @var array<string, array<int, bool>>
     */
    protected $fallbackIndexByRoutePath = [];
    /**
     * @var array<string, array<int, bool>>
     */
    protected $fallbackIndexByRouteTag = [];

    /**
     * @var array<string, int>
     */
    protected $fallbackMapKeyToId = [];


    public function __get($name)
    {
        switch ( $name ):
            case 'fallbackList':
                return $this->fallbackList;


            case 'fallbackIndexByRouteId':
                return $this->fallbackIndexByRouteId;

            case 'fallbackIndexByRoutePath':
                return $this->fallbackIndexByRoutePath;

            case 'fallbackIndexByRouteTag':
                return $this->fallbackIndexByRouteTag;


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
     * @return RouterGenericHandlerFallback[]
     */
    public function getFallbackList() : array
    {
        return $this->fallbackList;
    }


    public function hasFallback(int $id, ?RouterGenericHandlerFallback &$refFallback = null) : bool
    {
        $refFallback = null;

        if (isset($this->fallbackList[ $id ])) {
            $refFallback = $this->fallbackList[ $id ];

            return true;
        }

        return false;
    }

    public function getFallback(int $id) : RouterGenericHandlerFallback
    {
        return $this->fallbackList[ $id ];
    }


    public function registerFallback(RouterGenericHandlerFallback $fallback) : int
    {
        $key = $fallback->getKey();

        $id = $this->fallbackMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = ++$this->fallbackLastId;

            $this->fallbackList[ $id ] = $fallback;

            $this->fallbackMapKeyToId[ $key ] = $id;
        }

        return $id;
    }


    public function addRouteIdFallback(int $routeId, RouterGenericHandlerFallback $fallback) : int
    {
        $id = $this->registerFallback($fallback);

        $this->fallbackIndexByRouteId[ $routeId ][ $id ] = true;

        return $id;
    }

    public function addRoutePathFallback(RoutePath $routePath, RouterGenericHandlerFallback $fallback) : int
    {
        $id = $this->registerFallback($fallback);

        $this->fallbackIndexByRoutePath[ $routePath->getValue() ][ $id ] = true;

        return $id;
    }

    public function addRouteTagFallback(RouteTag $routeTag, RouterGenericHandlerFallback $fallback) : int
    {
        $id = $this->registerFallback($fallback);

        $this->fallbackIndexByRouteTag[ $routeTag->getValue() ][ $id ] = true;

        return $id;
    }
}
