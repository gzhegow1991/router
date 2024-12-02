<?php

namespace Gzhegow\Router\Handler\Demo\Action;

use Gzhegow\Router\Handler\Action\CorsAction;


class DemoCorsAction extends CorsAction
{
    public function __invoke($input = null, $context = null, $inputOriginal = null)
    {
        echo __METHOD__ . PHP_EOL;

        parent::__invoke($input, $context, $inputOriginal);
    }
}
