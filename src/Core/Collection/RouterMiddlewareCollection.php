<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Route\Struct\Tag;
use Gzhegow\Router\Core\Route\Struct\Path;
use Gzhegow\Router\Exception\RuntimeException;
use Gzhegow\Router\Core\Handler\Middleware\GenericHandlerMiddleware;


/**
 * @property GenericHandlerMiddleware[]      $middlewareList
 *
 * @property array<string, array<int, bool>> $middlewareIndexByPath
 * @property array<string, array<int, bool>> $middlewareIndexByTag
 *
 * @property array<string, int>              $middlewareMapKeyToId
 */
class RouterMiddlewareCollection implements \Serializable
{
    /**
     * @var int
     */
    protected $middlewareLastId = 0;

    /**
     * @var GenericHandlerMiddleware[]
     */
    protected $middlewareList = [];

    /**
     * @var array<string, array<int, bool>>
     */
    protected $middlewareIndexByPath = [];
    /**
     * @var array<string, array<int, bool>>
     */
    protected $middlewareIndexByTag = [];

    /**
     * @var array<string, int>
     */
    protected $middlewareMapKeyToId;


    public function __get($name)
    {
        switch ( $name ):
            case 'middlewareList':
                return $this->middlewareList;

            case 'middlewareIndexByPath':
                return $this->middlewareIndexByPath;

            case 'middlewareIndexByTag':
                return $this->middlewareIndexByTag;

            case 'middlewareMapKeyToId':
                return $this->middlewareMapKeyToId;

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
     * @return GenericHandlerMiddleware[]
     */
    public function getMiddlewareList() : array
    {
        return $this->middlewareList;
    }

    public function getMiddleware(int $id) : GenericHandlerMiddleware
    {
        return $this->middlewareList[ $id ];
    }

    public function registerMiddleware(GenericHandlerMiddleware $middleware) : int
    {
        $key = $middleware->getKey();

        $id = $this->middlewareMapKeyToId[ $key ] ?? null;

        if (null === $id) {
            $id = ++$this->middlewareLastId;

            $this->middlewareList[ $id ] = $middleware;

            $this->middlewareMapKeyToId[ $key ] = $id;
        }

        return $id;
    }


    public function addPathMiddleware(Path $path, GenericHandlerMiddleware $middleware) : int
    {
        $id = $this->registerMiddleware($middleware);

        $this->middlewareIndexByPath[ $path->getValue() ][ $id ] = true;

        return $id;
    }

    public function addTagMiddleware(Tag $tag, GenericHandlerMiddleware $middleware) : int
    {
        $id = $this->registerMiddleware($middleware);

        $this->middlewareIndexByTag[ $tag->getValue() ][ $id ] = true;

        return $id;
    }
}
