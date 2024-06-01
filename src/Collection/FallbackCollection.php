<?php

namespace Gzhegow\Router\Collection;

use Gzhegow\Router\Route\Struct\Tag;
use Gzhegow\Router\Route\Struct\Path;
use Gzhegow\Router\Handler\Fallback\GenericFallback;


class FallbackCollection
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var GenericFallback[]
     */
    public $fallbackList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    public $fallbackIndexByPath;
    /**
     * @var array<string, array<int, bool>>
     */
    public $fallbackIndexByTag;

    /**
     * @var array<string, int>
     */
    public $fallbackMapKeyToId;


    public function getFallback(int $id) : GenericFallback
    {
        return $this->fallbackList[ $id ];
    }


    public function addPathFallback(Path $path, GenericFallback $fallback) : int
    {
        $id = $this->registerFallback($fallback);

        $this->fallbackIndexByPath[ $path->getValue() ][ $id ] = true;

        return $id;
    }

    public function addTagFallback(Tag $tag, GenericFallback $fallback) : int
    {
        $id = $this->registerFallback($fallback);

        $this->fallbackIndexByTag[ $tag->getValue() ][ $id ] = true;

        return $id;
    }


    public function registerFallback(GenericFallback $fallback) : int
    {
        $key = $fallback->getKey();

        $id = $this->fallbackMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = $this->id++;

            $this->fallbackList[ $id ] = $fallback;

            $this->fallbackMapKeyToId[ $key ] = $id;
        }

        return $id;
    }
}
