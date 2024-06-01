<?php

namespace Gzhegow\Router\Handler\Demo\Fallback;


class DemoLogicFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null)
    {
        if (! is_a($e, \LogicException::class)) return null;

        var_dump(__METHOD__);

        return true;
    }
}
