<?php

namespace Gzhegow\Router\Handler\Demo\Fallback;


class DemoFallback
{
    public function __invoke(\Throwable $e, $input = null, $context = null)
    {
        if (is_a($e, \LogicException::class)) return false;

        var_dump(__METHOD__);

        return false;
    }
}
