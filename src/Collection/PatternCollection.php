<?php

namespace Gzhegow\Router\Collection;

use Gzhegow\Router\Pattern\Pattern;


class PatternCollection
{
    /**
     * @var Pattern[]
     */
    public $patternDict = [];


    public function getPattern(string $pattern) : Pattern
    {
        return $this->patternDict[ $pattern ];
    }


    public function registerPattern(Pattern $pattern) : string
    {
        if (! isset($this->patternDict[ $pattern->pattern ])) {
            $this->patternDict[ $pattern->pattern ] = $pattern;
        }

        return $pattern->pattern;
    }
}
