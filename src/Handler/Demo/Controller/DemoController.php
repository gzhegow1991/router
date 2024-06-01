<?php

namespace Gzhegow\Router\Handler\Demo\Controller;

class DemoController
{
    public function mainGet()
    {
        var_dump(__METHOD__);

        return 1;
    }

    public function mainPost()
    {
        var_dump(__METHOD__);

        return 1;
    }


    public function logic()
    {
        var_dump(__METHOD__);

        throw new \LogicException();
    }

    public function runtime()
    {
        var_dump(__METHOD__);

        throw new \RuntimeException();
    }
}
