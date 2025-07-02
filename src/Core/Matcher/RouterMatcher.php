<?php

namespace Gzhegow\Router\Core\Matcher;

use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;


class RouterMatcher implements RouterMatcherInterface
{
    /**
     * @var RouterRouteCollection
     */
    protected $routeCollection;


    public function initialize(RouterInterface $router) : void
    {
        $this->routeCollection = $router->getRouteCollection();
    }


    /**
     * @param int[] $ids
     *
     * @return Route[]
     */
    public function matchAllByIds($ids) : array
    {
        $result = [];

        $idList = (array) $ids;

        $routeList = $this->routeCollection->routeList;

        foreach ( $idList as $id ) {
            if (isset($routeList[ $id ])) {
                $result[ $id ] = $routeList[ $id ];
            }
        }

        return $result;
    }

    public function matchFirstByIds($ids) : ?Route
    {
        $result = null;

        $idList = (array) $ids;

        $routeList = $this->routeCollection->routeList;

        foreach ( $idList as $id ) {
            if (isset($routeList[ $id ])) {
                $result = $routeList[ $id ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param string[] $names
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames($names, ?bool $unique = null) : array
    {
        $result = [];

        $nameList = (array) $names;
        $isUnique = $unique ?? false;

        $routeIndexByName = $this->routeCollection->routeIndexByName;

        $matchIndex = [];
        $namesIndex = [];
        foreach ( $nameList as $idx => $name ) {
            $result[ $idx ] = [];

            if (isset($routeIndexByName[ $name ])) {
                $matchIndex += $routeIndexByName[ $name ];
            }

            if (! $isUnique) {
                $namesIndex[ $name ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        if ($isUnique) {
            $result = $routesMatch;

        } else {
            foreach ( $routesMatch as $route ) {
                /** @var Route $route */

                foreach ( $namesIndex[ $route->name ] ?? [] as $idx => $bool ) {
                    $result[ $idx ][ $route->id ] = $route;
                }
            }
        }

        return $result;
    }

    public function matchFirstByNames($names) : ?Route
    {
        $result = null;

        $nameList = (array) $names;

        $routeIndexByName = $this->routeCollection->routeIndexByName;

        $matchIndex = [];
        foreach ( $nameList as $name ) {
            if (isset($routeIndexByName[ $name ])) {
                $matchIndex += $routeIndexByName[ $name ];
            }

            if (count($matchIndex)) {
                $result = $this->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param string[] $tags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags($tags, ?bool $unique = null) : array
    {
        $result = [];

        $tagList = (array) $tags;
        $isUnique = $unique ?? false;

        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        $tagsIndex = [];
        foreach ( $tagList as $idx => $tag ) {
            $result[ $idx ] = [];

            if (isset($routeIndexByTag[ $tag ])) {
                $matchIndex += $routeIndexByTag[ $tag ];
            }

            if (! $isUnique) {
                $tagsIndex[ $tag ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        if ($isUnique) {
            $result = $routesMatch;

        } else {
            foreach ( $routesMatch as $route ) {
                /** @var Route $route */

                foreach ( $route->tagIndex as $tag => $b ) {
                    foreach ( $tagsIndex[ $tag ] ?? [] as $idx => $bb ) {
                        $result[ $idx ][ $route->id ] = $route;
                    }
                }
            }
        }

        return $result;
    }

    public function matchFirstByTags($tags) : ?Route
    {
        $result = null;

        $tagList = (array) $tags;

        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        foreach ( $tagList as $tag ) {
            if (isset($routeIndexByTag[ $tag ])) {
                $matchIndex += $routeIndexByTag[ $tag ];
            }

            if (count($matchIndex)) {
                $result = $this->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContract $contract) : array
    {
        $intersect = [];

        if ($contract->idIndex) {
            $intersect[] = $contract->idIndex;
        }

        if ($contract->nameIndex) {
            $index = [];
            foreach ( $contract->nameIndex as $name => $bool ) {
                $index += $this->routeCollection->routeIndexByName[ $name ] ?? [];
            }

            $intersect[] = $index;
        }

        if ($contract->tagIndex) {
            $index = [];
            foreach ( $contract->tagIndex as $tag => $bool ) {
                $index += $this->routeCollection->routeIndexByTag[ $tag ] ?? [];
            }

            $intersect[] = $index;
        }

        if ([] !== $intersect) {
            $index = (count($intersect) > 1)
                ? array_intersect_key(...$intersect)
                : $intersect;

        } else {
            $index = array_fill_keys(
                array_keys($this->routeCollection->routeList),
                true
            );
        }

        $hasHttpMethodIndex = ([] !== $contract->httpMethodIndex);
        $hasPathIndex = ([] !== $contract->pathIndex);

        $result = [];

        foreach ( $index as $id => $b ) {
            $route = $this->routeCollection->routeList[ $id ];

            if ($hasHttpMethodIndex) {
                if (! array_intersect_key($route->httpMethodIndex, $contract->httpMethodIndex)) {
                    continue;
                }
            }

            if ($hasPathIndex) {
                $found = false;
                foreach ( $contract->pathIndex as $path => $bb ) {
                    $found = (0 === strpos($route->path, $path));

                    if ($found) break;
                }

                if (! $found) continue;
            }

            $result[ $id ] = $route;
        }

        return $result;
    }
}
