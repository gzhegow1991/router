<?php

namespace Gzhegow\Router\Core\Store;

use Gzhegow\Router\Core\Pattern\RouterPattern;
use Gzhegow\Router\Exception\RuntimeException;


/**
 * @property RouterPattern[] $patternDict
 */
class RouterPatternCollection implements \Serializable
{
    /**
     * @var RouterPattern[]
     */
    protected $patternDict = [];


    public function __get($name)
    {
        switch ( $name ):
            case 'patternDict':
                return $this->patternDict;

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
     * @return RouterPattern[]
     */
    public function getPatternDict() : array
    {
        return $this->patternDict;
    }


    public function hasPattern(string $pattern, ?RouterPattern &$refPattern = null) : bool
    {
        $refPattern = null;

        if (isset($this->patternDict[ $pattern ])) {
            $refPattern = $this->patternDict[ $pattern ];

            return true;
        }

        return false;
    }

    public function getPattern(string $pattern) : RouterPattern
    {
        return $this->patternDict[ $pattern ];
    }


    public function registerPattern(RouterPattern $pattern) : string
    {
        if (isset($this->patternDict[ $pattern->pattern ])) {
            throw new RuntimeException(
                [ 'The `pattern` is already exists: ' . $pattern->pattern ]
            );
        }

        $this->patternDict[ $pattern->pattern ] = $pattern;

        return $pattern->pattern;
    }
}
