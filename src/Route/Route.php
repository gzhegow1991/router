<?php

namespace Gzhegow\Router\Route;

use Gzhegow\Router\Handler\Action\GenericHandlerAction;
use Gzhegow\Router\Handler\Fallback\GenericHandlerFallback;
use Gzhegow\Router\Handler\Middleware\GenericHandlerMiddleware;


class Route implements \Serializable
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $path;
    /**
     * @var string
     */
    public $compiledPathRegex;

    /**
     * @var GenericHandlerAction
     */
    public $action;
    /**
     * @var array<string, mixed>
     */
    public $compiledActionAttributes = [];

    /**
     * @var string
     */
    public $name;

    /**
     * @var array<string, bool>
     */
    public $httpMethodIndex = [];
    /**
     * @var array<string, bool>
     */
    public $tagIndex = [];

    /**
     * @var array<string, mixed>
     */
    public $dispatchActionAttributes = [];
    /**
     * @var array<string, GenericHandlerMiddleware>
     */
    public $dispatchMiddlewareIndex = [];
    /**
     * @var array<string, GenericHandlerFallback>
     */
    public $dispatchFallbackIndex = [];


    public function getId() : int
    {
        return $this->id;
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
}
