<?php

namespace Gzhegow\Router\Core\Matcher;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Route\Struct\RouteNameTag;
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
     * @param int[] $routeIds
     *
     * @return Route[]
     */
    public function matchAllByIds(array $routeIds) : array
    {
        $theParseThrow = Lib::parseThrow();

        $result = [];

        $idList = [];
        foreach ( $routeIds as $idx => $routeId ) {
            $idList[ $idx ] = $theParseThrow->int_non_negative($routeId);
        }

        $routeList = $this->routeCollection->routeList;

        // > отбрасываем оригинальные индексы, поскольку работаем с первичными
        foreach ( $idList as $id ) {
            if (isset($routeList[ $id ])) {
                $result[ $id ] = $routeList[ $id ];
            }
        }

        return $result;
    }

    /**
     * @param int[] $routeIds
     */
    public function matchFirstByIds(array $routeIds) : ?Route
    {
        $theParseThrow = Lib::parseThrow();

        $result = null;

        $idList = [];
        foreach ( $routeIds as $idx => $routeId ) {
            $idList[ $idx ] = $theParseThrow->int_non_negative($routeId);
        }

        $routeList = $this->routeCollection->routeList;

        // > отбрасываем оригинальные индексы, поскольку работаем с первичными
        foreach ( $idList as $id ) {
            if (isset($routeList[ $id ])) {
                $result = $routeList[ $id ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param (string|RouteName)[] $routeNames
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $routeNames, ?bool $unique = null) : array
    {
        $unique = $unique ?? false;

        $result = [];

        $nameList = [];
        foreach ( $routeNames as $idx => $routeName ) {
            $nameList[ $idx ] = RouteName::from($routeName);
        }

        $routeIndexByName = $this->routeCollection->routeIndexByName;

        $matchIndex = [];
        $namesIndex = [];
        foreach ( $nameList as $idx => $name ) {
            $result[ $idx ] = [];

            $nameString = $name->getValue();

            if (isset($routeIndexByName[ $nameString ])) {
                $matchIndex += $routeIndexByName[ $nameString ];
            }

            if (! $unique) {
                $namesIndex[ $nameString ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        if ($unique) {
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

    /**
     * @param (string|RouteName)[] $routeNames
     */
    public function matchFirstByNames(array $routeNames) : ?Route
    {
        $result = null;

        $nameList = [];
        foreach ( $routeNames as $idx => $routeName ) {
            $nameList[ $idx ] = RouteName::from($routeName);
        }

        $routeIndexByName = $this->routeCollection->routeIndexByName;

        $matchIndex = [];
        foreach ( $nameList as $name ) {
            $nameString = $name->getValue();

            if (isset($routeIndexByName[ $nameString ])) {
                $matchIndex += $routeIndexByName[ $nameString ];
            }

            if ([] !== $matchIndex) {
                $result = $this->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param (string|RouteTag)[] $routeTags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $routeTags, ?bool $unique = null) : array
    {
        $unique = $unique ?? false;

        $result = [];

        $tagList = [];
        foreach ( $routeTags as $idx => $routeTag ) {
            $tagList[ $idx ] = RouteTag::from($routeTag);
        }

        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        $tagsIndex = [];
        foreach ( $tagList as $idx => $tag ) {
            $result[ $idx ] = [];

            $tagString = $tag->getValue();

            if (isset($routeIndexByTag[ $tagString ])) {
                $matchIndex += $routeIndexByTag[ $tagString ];
            }

            if (! $unique) {
                $tagsIndex[ $tagString ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        if ($unique) {
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

    /**
     * @param (string|RouteTag)[] $routeTags
     */
    public function matchFirstByTags(array $routeTags) : ?Route
    {
        $result = null;

        $tagList = [];
        foreach ( $routeTags as $idx => $routeTag ) {
            $tagList[ $idx ] = RouteTag::from($routeTag);
        }

        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        foreach ( $tagList as $tag ) {
            $tagString = $tag->getValue();

            if (isset($routeIndexByTag[ $tagString ])) {
                $matchIndex += $routeIndexByTag[ $tagString ];
            }

            if ([] !== $matchIndex) {
                $result = $this->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param (array{ 0: string, 1: string }|RouteNameTag)[] $routeNameTags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNameTags(array $routeNameTags, ?bool $unique = null) : array
    {
        $unique = $unique ?? false;

        $result = [];

        $nameTagList = [];
        foreach ( $routeNameTags as $idx => $routeNameTag ) {
            $nameTagList[ $idx ] = RouteNameTag::from($routeNameTag);
        }

        $routeIndexByName = $this->routeCollection->routeIndexByName;
        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        $nameTagsIndex = [];
        foreach ( $nameTagList as $idx => $nameTag ) {
            $result[ $idx ] = [];

            [ $nameString, $tagString ] = $nameTag->getPair();

            $intersect = [];

            if (null !== $nameString) {
                if (isset($routeIndexByName[ $nameString ])) {
                    $intersect[] = $routeIndexByName[ $nameString ];
                }
            }

            if (null !== $tagString) {
                if (isset($routeIndexByTag[ $tagString ])) {
                    $intersect[] = $routeIndexByTag[ $tagString ];
                }
            }

            if (count($intersect) > 1) {
                $matchIndex += array_intersect_key(...$intersect);

            } elseif ([] !== $intersect) {
                $matchIndex += reset($intersect);
            }

            if (! $unique) {
                $nameTagsIndex[ "\0{$nameString}\0{$tagString}\0" ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routeCollection->routeList[ $id ];
        }

        if ($unique) {
            $result = $routesMatch;

        } else {
            foreach ( $routesMatch as $route ) {
                /** @var Route $route */

                $routeName = $route->name;

                if ([] === $route->tagIndex) {
                    $key = "\0{$routeName}\0";

                    foreach ( $nameTagsIndex as $indexKey => $indexes ) {
                        if (false === strpos($key, $indexKey)) {
                            continue;
                        }

                        foreach ( $indexes as $idx => $b ) {
                            $result[ $idx ][ $route->id ] = $route;
                        }
                    }

                } else {
                    foreach ( $route->tagIndex as $tagString => $b ) {
                        $key = "\0{$routeName}\0{$tagString}\0";

                        foreach ( $nameTagsIndex as $indexKey => $indexes ) {
                            if (false === strpos($key, $indexKey)) {
                                continue;
                            }

                            foreach ( $indexes as $idx => $bb ) {
                                $result[ $idx ][ $route->id ] = $route;
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param (array{ 0: string, 1: string }|RouteNameTag)[] $routeNameTags
     */
    public function matchFirstByNameTags(array $routeNameTags) : ?Route
    {
        $result = null;

        $nameTagList = [];
        foreach ( $routeNameTags as $idx => $routeNameTag ) {
            $nameTagList[ $idx ] = RouteNameTag::from($routeNameTag);
        }

        $routeIndexByName = $this->routeCollection->routeIndexByName;
        $routeIndexByTag = $this->routeCollection->routeIndexByTag;

        $matchIndex = [];
        foreach ( $nameTagList as $nameTag ) {
            [ $nameString, $tagString ] = $nameTag->getPair();

            $intersect = [];

            if (null !== $nameString) {
                if (isset($routeIndexByName[ $nameString ])) {
                    $intersect[] = $routeIndexByName[ $nameString ];
                }
            }

            if (null !== $tagString) {
                if (isset($routeIndexByTag[ $tagString ])) {
                    $intersect[] = $routeIndexByTag[ $tagString ];
                }
            }

            if (count($intersect) > 1) {
                $matchIndex += array_intersect_key(...$intersect);

            } elseif ([] !== $intersect) {
                $matchIndex += reset($intersect);
            }

            if ([] !== $matchIndex) {
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
                if (! array_intersect_key($route->methodIndex, $contract->httpMethodIndex)) {
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
