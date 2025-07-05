<?php

namespace Gzhegow\Router\Demo\Handler\Action;

use Gzhegow\Router\Core\Http\Action\CorsAction;


class DemoCorsAction extends CorsAction
{
    public function __invoke(
        $input,
        array $context = [],
        array $args = []
    )
    {
        echo __METHOD__ . "\n";

        parent::__invoke($input, $context);
    }
}
