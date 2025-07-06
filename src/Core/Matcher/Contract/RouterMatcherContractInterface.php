<?php

namespace Gzhegow\Router\Core\Matcher\Contract;

use Gzhegow\Router\Core\Route\Route;


interface RouterMatcherContractInterface
{
    public function isMatch(Route $route) : bool;
}
