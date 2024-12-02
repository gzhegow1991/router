<?php

namespace Gzhegow\Router\Handler\Demo\Fallback;

use Gzhegow\Pipeline\Handler\Demo\Fallback\DemoRuntimeExceptionFallback as PipelineDemoRuntimeExceptionFallback;


class DemoRuntimeExceptionFallback extends PipelineDemoRuntimeExceptionFallback
{
    // public function __invoke(\Throwable $e, $input = null, $context = null, $state = null)
    // {
    //     return parent::__invoke($e, $input, $context, $state);
    // }
}
