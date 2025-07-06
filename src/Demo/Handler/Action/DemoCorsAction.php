<?php

namespace Gzhegow\Router\Demo\Handler\Action;

use Gzhegow\Router\Core\Http\Action\RouterCorsAction;


class DemoCorsAction extends RouterCorsAction
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
