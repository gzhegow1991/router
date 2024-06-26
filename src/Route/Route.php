<?php

namespace Gzhegow\Router\Route;

use Gzhegow\Router\Handler\Action\GenericAction;
use Gzhegow\Router\Handler\Fallback\GenericFallback;
use Gzhegow\Router\Handler\Middleware\GenericMiddleware;


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
     * @var GenericAction
     */
    public $action;
    /**
     * @var array<string, mixed>
     */
    public $compiledActionAttributes;

    /**
     * @var string
     */
    public $name;

    /**
     * @var array<string, bool>
     */
    public $httpMethodIndex;
    /**
     * @var array<string, bool>
     */
    public $tagIndex;

    /**
     * @var array<string, mixed>
     */
    public $contractActionAttributes;
    /**
     * @var array<string, GenericMiddleware>
     */
    public $contractMiddlewareIndex;
    /**
     * @var array<string, GenericFallback>
     */
    public $contractFallbackIndex;


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
