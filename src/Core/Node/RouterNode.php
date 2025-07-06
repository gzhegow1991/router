<?php

namespace Gzhegow\Router\Core\Node;


class RouterNode implements \Serializable
{
    /**
     * @var string
     */
    public $part;

    /**
     * @var array<string, RouterNode>
     */
    public $childrenByPart = [];
    /**
     * @var array<string, RouterNode>
     */
    public $childrenByRegex = [];

    /**
     * @var array<string, array<int, bool>>
     */
    public $routeIndexByPart = [];
    /**
     * @var array<string, array<int, bool>>
     */
    public $routeIndexByRegex = [];
    /**
     * @var array<string, array<int, bool>>
     */
    public $routeIndexByMethod = [];


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
