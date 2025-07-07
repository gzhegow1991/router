<?php

namespace Gzhegow\Router\Core\Matcher;

use Gzhegow\Lib\Lib;
use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;
use Gzhegow\Router\Core\Store\RouterStore;
use Gzhegow\Router\Core\Route\Struct\RouteTag;
use Gzhegow\Router\Core\Route\Struct\RouteName;
use Gzhegow\Router\Core\Matcher\Contract\DefaultRouterMatcherContract;
use Gzhegow\Router\Core\Matcher\Contract\RouterMatcherContractInterface;


class RouterMatcher implements RouterMatcherInterface
{
    /**
     * @var RouterStore
     */
    protected $routerStore;


    public function initialize(RouterInterface $router) : void
    {
        $this->routerStore = $router->getRouterStore();
    }


    /**
     * @param int[] $idList
     *
     * @return Route[]
     */
    public function matchAllByIds(array $idList) : array
    {
        $theParseThrow = Lib::parseThrow();

        $result = [];

        $routeList = $this->routerStore->routeCollection->routeList;

        $routeIdList = [];
        foreach ( $idList as $idx => $id ) {
            $routeIdList[ $idx ] = $theParseThrow->int_non_negative($id);
        }

        // > отбрасываем оригинальные индексы, поскольку работаем с первичными и будет только одно совпадение
        foreach ( $routeIdList as $routeId ) {
            if (isset($routeList[ $routeId ])) {
                $result[ $routeId ] = $routeList[ $routeId ];
            }
        }

        return $result;
    }

    /**
     * @param int[] $idList
     */
    public function matchFirstByIds(array $idList) : ?Route
    {
        $theParseThrow = Lib::parseThrow();

        $result = null;

        $routeList = $this->routerStore->routeCollection->routeList;

        $routeIdList = [];
        foreach ( $idList as $idx => $id ) {
            $routeIdList[ $idx ] = $theParseThrow->int_non_negative($id);
        }

        // > отбрасываем оригинальные индексы, поскольку работаем с первичными и будет только одно совпадение
        foreach ( $routeIdList as $routeId ) {
            if (isset($routeList[ $routeId ])) {
                $result = $routeList[ $routeId ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param (string|RouteName)[] $nameList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames(array $nameList, ?bool $unique = null) : array
    {
        $unique = $unique ?? false;

        $result = [];

        $routeIndexByName = $this->routerStore->routeCollection->routeIndexByName;

        $routeNameList = [];
        foreach ( $nameList as $idx => $name ) {
            $routeNameList[ $idx ] = RouteName::from($name);
        }

        $matchIndex = [];
        $namesIndex = [];
        foreach ( $routeNameList as $idx => $routeName ) {
            $result[ $idx ] = [];

            $nameString = $routeName->getValue();

            if (isset($routeIndexByName[ $nameString ])) {
                $matchIndex += $routeIndexByName[ $nameString ];
            }

            if (! $unique) {
                $namesIndex[ $nameString ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routerStore->routeCollection->routeList[ $id ];
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
     * @param (string|RouteName)[] $nameList
     */
    public function matchFirstByNames(array $nameList) : ?Route
    {
        $result = null;

        $routeIndexByName = $this->routerStore->routeCollection->routeIndexByName;

        $routeNameList = [];
        foreach ( $nameList as $idx => $name ) {
            $routeNameList[ $idx ] = RouteName::from($name);
        }

        $matchIndex = [];
        foreach ( $routeNameList as $routeName ) {
            $nameString = $routeName->getValue();

            if (isset($routeIndexByName[ $nameString ])) {
                $matchIndex += $routeIndexByName[ $nameString ];
            }

            if ([] !== $matchIndex) {
                $result = $this->routerStore->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param (string|RouteTag)[] $tagList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags(array $tagList, ?bool $unique = null) : array
    {
        $unique = $unique ?? false;

        $result = [];

        $routeIndexByTag = $this->routerStore->routeCollection->routeIndexByTag;

        $routeTagList = [];
        foreach ( $tagList as $idx => $tag ) {
            $routeTagList[ $idx ] = RouteTag::from($tag);
        }

        $matchIndex = [];
        $tagsIndex = [];
        foreach ( $routeTagList as $idx => $routeTag ) {
            $result[ $idx ] = [];

            $tagString = $routeTag->getValue();

            if (isset($routeIndexByTag[ $tagString ])) {
                $matchIndex += $routeIndexByTag[ $tagString ];
            }

            if (! $unique) {
                $tagsIndex[ $tagString ][ $idx ] = true;
            }
        }

        $routesMatch = [];
        foreach ( $matchIndex as $id => $bool ) {
            $routesMatch[ $id ] = $this->routerStore->routeCollection->routeList[ $id ];
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
     * @param (string|RouteTag)[] $tagList
     */
    public function matchFirstByTags(array $tagList) : ?Route
    {
        $result = null;

        $routeIndexByTag = $this->routerStore->routeCollection->routeIndexByTag;

        $routeTagList = [];
        foreach ( $tagList as $idx => $tag ) {
            $routeTagList[ $idx ] = RouteTag::from($tag);
        }

        $matchIndex = [];
        foreach ( $routeTagList as $routeTag ) {
            $tagString = $routeTag->getValue();

            if (isset($routeIndexByTag[ $tagString ])) {
                $matchIndex += $routeIndexByTag[ $tagString ];
            }

            if ([] !== $matchIndex) {
                $result = $this->routerStore->routeCollection->routeList[ key($matchIndex) ];

                break;
            }
        }

        return $result;
    }


    /**
     * @param array{
     *     name: string|false|null,
     *     tag: string|false|null,
     *     method: string|false|null,
     *     path: string|false|null,
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     *     3: string|false|null,
     * }[] $paramsList
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByParams(array $paramsList, ?bool $unique = null) : array
    {
        $unique = $unique ?? false;

        $result = [];

        foreach ( $paramsList as $idx => $array ) {
            $contract = DefaultRouterMatcherContract::from($array);

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
     *     name: string|false|null,
     *     tag: string|false|null,
     *     method: string|false|null,
     *     path: string|false|null,
     *     0: string|false|null,
     *     1: string|false|null,
     *     2: string|false|null,
     *     3: string|false|null,
     * }[] $paramsList
     */
    public function matchFirstByParams(array $paramsList) : ?Route
    {
        $result = null;

        foreach ( $paramsList as $array ) {
            $contract = DefaultRouterMatcherContract::from($array);

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

        $routeList = $this->routerStore->routeCollection->routeList;

        foreach ( $routeList as $routeId => $route ) {
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

        $routeList = $this->routerStore->routeCollection->routeList;

        foreach ( $routeList as $route ) {
            if (! $contract->isMatch($route)) {
                continue;
            }

            $result = $route;

            break;
        }

        return $result;
    }
}
