<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Struct\Tag;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Core\Handler\Fallback\GenericHandlerFallback;


/**
 * @property GenericHandlerFallback[]        $fallbackList
 *
 * @property array<string, array<int, bool>> $fallbackIndexByPath
 * @property array<string, array<int, bool>> $fallbackIndexByTag
 *
 * @property array<string, int>              $fallbackMapKeyToId
 */
class RouterFallbackCollection implements \Serializable
{
    /**
     * @var int
     */
    protected $fallbackLastId = 0;

    /**
     * @var GenericHandlerFallback[]
     */
    protected $fallbackList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    protected $fallbackIndexByPath = [];
    /**
     * @var array<string, array<int, bool>>
     */
    protected $fallbackIndexByTag = [];

    /**
     * @var array<string, int>
     */
    protected $fallbackMapKeyToId = [];


    public function __get($name)
    {
        switch ( $name ):
            case 'fallbackList':
                return $this->fallbackList;

            case 'fallbackIndexByPath':
                return $this->fallbackIndexByPath;

            case 'fallbackIndexByTag':
                return $this->fallbackIndexByTag;

            case 'fallbackMapKeyToId':
                return $this->fallbackMapKeyToId;

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
     * @return GenericHandlerFallback[]
     */
    public function getFallbackList() : array
    {
        return $this->fallbackList;
    }

    public function getFallback(int $id) : GenericHandlerFallback
    {
        return $this->fallbackList[ $id ];
    }

    public function registerFallback(GenericHandlerFallback $fallback) : int
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
}
