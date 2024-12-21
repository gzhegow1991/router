<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Struct\Tag;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;


class RouterFallbackCollection
{
    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var GenericHandlerFallback[]
     */
    public $fallbackList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    public $fallbackIndexByPath = [];
    /**
     * @var array<string, array<int, bool>>
     */
    public $fallbackIndexByTag = [];

    /**
     * @var array<string, int>
     */
    public $fallbackMapKeyToId = [];


    public function getFallback(int $id) : GenericHandlerFallback
    {
        return $this->fallbackList[ $id ];
    }


    public function addPathFallback(Path $path, GenericHandlerFallback $fallback) : int
    {
        $id = $this->registerFallback($fallback);

        $this->fallbackIndexByPath[ $path->getValue() ][ $id ] = true;

        return $id;
    }

    public function addTagFallback(Tag $tag, GenericHandlerFallback $fallback) : int
    {
        $id = $this->registerFallback($fallback);

        $this->fallbackIndexByTag[ $tag->getValue() ][ $id ] = true;

        return $id;
    }


    public function registerFallback(GenericHandlerFallback $fallback) : int
    {
        $key = $fallback->getKey();

        $id = $this->fallbackMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = ++$this->id;

            $this->fallbackList[ $id ] = $fallback;

            $this->fallbackMapKeyToId[ $key ] = $id;
        }

        return $id;
    }
}
