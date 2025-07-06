<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Exception\RuntimeException;


/**
 * @property Route[]                         $routeList
 *
 * @property array<string, array<int, bool>> $routeIndexByName
 * @property array<string, array<int, bool>> $routeIndexByTag
 *
 * @property array<string, bool>             $routeMapHttpMethodPathToBoolean
 * @property array<string, bool>             $routeMapSplIdToBoolean
 */
class RouterRouteCollection implements \Serializable
{
    /**
     * @var int
     */
    protected $routeLastId = 0;

    /**
     * @var Route[]
     */
    protected $routeList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    protected $routeIndexByName = [];
    /**
     * @var array<string, array<int, bool>>
     */
    protected $routeIndexByTag = [];

    /**
     * @var array<string, bool>
     */
    protected $routeMapHttpMethodPathToBoolean = [];
    /**
     * @var array<string, bool>
     */
    protected $routeMapSplIdToBool = [];


    public function __get($name)
    {
        switch ( $name ):
            case 'routeList':
                return $this->routeList;

            case 'routeIndexByName':
                return $this->routeIndexByName;

            case 'routeIndexByTag':
                return $this->routeIndexByTag;

            case 'routeMapHttpMethodPathToBoolean':
                return $this->routeMapHttpMethodPathToBoolean;

            case 'routeMapSplIdToBool':
                return $this->routeMapSplIdToBool;

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
     * @return Route[]
     */
    public function getRouteList() : array
    {
        return $this->routeList;
    }


    public function hasRoute(int $id, ?Route &$refRoute = null) : bool
    {
        $refRoute = null;

        if (isset($this->routeList[ $id ])) {
            $refRoute = $this->routeList[ $id ];

            return true;
        }

        return false;
    }

    public function getRoute(int $id) : Route
    {
        return $this->routeList[ $id ];
    }


    public function registerRoute(Route $route) : int
    {
        $id = ++$this->routeLastId;

        $route->id = $id;

        $this->routeList[ $id ] = $route;

        $this->indexRoute($route);

        return $id;
    }


    protected function indexRoute(Route $route) : void
    {
        $routeSplId = spl_object_id($route);
        $routePath = $route->path;
        $routeName = $route->name;

        if (isset($this->routeMapSplIdToBool[ $routeSplId ])) {
            throw new RuntimeException(
                'Route with this `name` already exists: ' . $routeName
            );
        }

        $this->routeMapSplIdToBool[ $routeSplId ] = true;

        if (null === $routeName) {
            $this->routeIndexByName[ '!' ][ $route->id ] = true;

        } else {
            if (isset($this->routeIndexByName[ $routeName ])) {
                foreach ( $this->routeIndexByName[ $routeName ] as $routeId => $bool ) {
                    $existingRoute = $this->routeList[ $routeId ];

                    if (true
                        && $route->path === $existingRoute->path
                        && $route->methodIndex === $existingRoute->methodIndex
                        && $route->tagIndex === $existingRoute->tagIndex
                    ) {
                        throw new RuntimeException(
                            [ 'Route with this `name` already exists: ' . $routeName ]
                        );
                    }
                }
            }

            $this->routeIndexByName[ $routeName ][ $route->id ] = true;
        }

        foreach ( $route->methodIndex as $routeHttpMethod => $bool ) {
            $key = "{$routePath}\0{$routeHttpMethod}";

            if (isset($this->routeMapHttpMethodPathToBoolean[ $key ])) {
                throw new RuntimeException(
                    ''
                    . 'Route with this `path` already bound for this `httpMethod`: '
                    . '[ ' . $routePath . ' ]'
                    . '[ ' . $routeHttpMethod . ' ]'
                );
            }

            $this->routeMapHttpMethodPathToBoolean[ $key ] = true;
        }

        if ([] === $route->tagIndex) {
            $this->routeIndexByTag[ '!' ][ $route->id ] = true;

        } else {
            foreach ( $route->tagIndex as $routeTag => $bool ) {
                $this->routeIndexByTag[ $routeTag ][ $route->id ] = true;
            }
        }
    }
}
