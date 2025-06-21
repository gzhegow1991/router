<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Exception\RuntimeException;


class RouterRouteCollection implements \Serializable
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var Route[]
     */
    public $routeList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    public $routeIndexByName = [];
    /**
     * @var array<string, array<int, bool>>
     */
    public $routeIndexByTag = [];

    /**
     * @var array<string, string>
     */
    public $routeMapPathToName = [];
    /**
     * @var array<string, string>
     */
    public $routeMapHttpMethodPathToBoolean = [];


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


    public function getRoute(int $id) : Route
    {
        return $this->routeList[ $id ];
    }


    public function registerRoute(Route $route) : int
    {
        $id = ++$this->id;

        $route->id = $id;

        $this->routeList[ $id ] = $route;

        $this->indexRoute($route);

        return $id;
    }


    protected function indexRoute(Route $route) : void
    {
        $path = $route->path;
        $name = $route->name;

        if (null !== $name) {
            if (isset($this->routeMapPathToName[ $name ])) {
                $existingPath = $this->routeMapPathToName[ $name ];

                if ($existingPath !== $path) {
                    throw new RuntimeException(
                        'Route with this `name` already exists: ' . $name
                    );
                }

            } else {
                $this->routeMapPathToName[ $name ] = $path;
            }

            $this->routeIndexByName[ $name ][ $route->id ] = true;
        }

        foreach ( $route->httpMethodIndex as $httpMethod => $bool ) {
            $key = "{$path}\0{$httpMethod}";

            if (isset($this->routeMapHttpMethodPathToBoolean[ $key ])) {
                throw new RuntimeException(
                    'Route with this `path` already bound for this `httpMethod`: '
                    . '`' . $path . '`'
                    . ' / ' . '`' . $httpMethod . '`'
                );
            }

            $this->routeMapHttpMethodPathToBoolean[ $key ] = true;
        }

        foreach ( $route->tagIndex as $tag => $bool ) {
            $this->routeIndexByTag[ $tag ][ $route->id ] = true;
        }
    }
}
