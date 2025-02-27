<?php

namespace Gzhegow\Router\Demo\Handler\Action;

use Gzhegow\Router\Core\Handler\Action\CorsAction;


class DemoCorsAction extends CorsAction
{
    public function __invoke($input = null, $context = null)
    {
        echo __METHOD__ . PHP_EOL;

        parent::__invoke($input, $context);
    }
}
