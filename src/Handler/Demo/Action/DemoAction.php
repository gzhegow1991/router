<?php

namespace Gzhegow\Router\Handler\Demo\Action;

class DemoAction
{
    public function __invoke($input = null, $context = null) // : mixed
    {
        return $input;
    }
}
