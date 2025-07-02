<?php

namespace Gzhegow\Router\Core\Matcher;

use Gzhegow\Router\RouterInterface;
use Gzhegow\Router\Core\Route\Route;


interface RouterMatcherInterface
{
    public function initialize(RouterInterface $router) : void;


    /**
     * @param int[] $ids
     *
     * @return Route[]
     */
    public function matchAllByIds($ids) : array;

    public function matchFirstByIds($ids) : ?Route;


    /**
     * @param string[] $names
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByNames($names, ?bool $unique = null) : array;

    public function matchFirstByNames($names) : ?Route;


    /**
     * @param string[] $tags
     *
     * @return Route[]|Route[][]
     */
    public function matchAllByTags($tags, ?bool $unique = null) : array;

    public function matchFirstByTags($tags) : ?Route;


    /**
     * @return Route[]
     */
    public function matchByContract(RouterMatcherContract $contract) : array;
}
