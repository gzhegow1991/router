<?php

namespace Gzhegow\Router\Core\Matcher;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Collection\RouterRouteCollection;
use Gzhegow\Router\Core\Matcher\Contract\RouterNameTagMethodContract;
use Gzhegow\Router\Core\Matcher\Contract\RouterMatcherContractInterface;


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
     * @param array{
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     * }[] $routeNameTagMethods
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNameTagMethods(array $routeNameTagMethods, ?bool $unique = null) : array
    {
        $unique = $unique ?? false;

        $result = [];

        foreach ( $routeNameTagMethods as $idx => $array ) {
            $contract = RouterNameTagMethodContract::from($array);

            if ($unique) {
                $result += $this->matchByContract($contract);

            } else {
                $result[ $idx ] = $result[ $idx ] ?? [];
                $result[ $idx ] += $this->matchByContract($contract);
            }
        }

        return $result;
    }

    /**
     * @param array{
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     * }[] $routeNameTagMethods
     */
    public function matchFirstByNameTagMethods(array $routeNameTagMethods) : ?Route
    {
        $result = null;

        foreach ( $routeNameTagMethods as $array ) {
            $contract = RouterNameTagMethodContract::from($array);

            if ($route = $this->matchFirstByContract($contract)) {
                $result = $route;

                break;
            }
        }

        return $result;
    }


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContractInterface $contract) : array
    {
        $result = [];

        foreach ( $this->routeCollection->routeList as $routeId => $route ) {
            if (! $contract->isMatch($route)) {
                continue;
            }

            $result[ $routeId ] = $route;
        }

        return $result;
    }

    public function matchFirstByContract(RouterMatcherContractInterface $contract) : ?Route
    {
        $result = null;

        foreach ( $this->routeCollection->routeList as $route ) {
            if (! $contract->isMatch($route)) {
                continue;
            }

            $result = $route;

            break;
        }

        return $result;
    }
}
