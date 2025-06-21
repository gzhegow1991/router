<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Pattern\RouterPattern;


class RouterPatternCollection implements \Serializable
{
    /**
     * @var RouterPattern[]
     */
    public $patternDict = [];


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


    public function getPattern(string $pattern) : RouterPattern
    {
        return $this->patternDict[ $pattern ];
    }


    public function registerPattern(RouterPattern $pattern) : string
    {
        if (! isset($this->patternDict[ $pattern->pattern ])) {
            $this->patternDict[ $pattern->pattern ] = $pattern;
        }

        return $pattern->pattern;
    }
}
