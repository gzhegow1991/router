<?php

namespace Gzhegow\Router\Core\Collection;

use Gzhegow\Router\Core\Pattern\RouterPattern;


class RouterPatternCollection
{
    /**
     * @var RouterPattern[]
     */
    public $patternDict = [];


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
