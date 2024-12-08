<?php

namespace Gzhegow\Router\Lib;

use Gzhegow\Router\Lib\Traits\OsTrait;
use Gzhegow\Router\Lib\Traits\PhpTrait;
use Gzhegow\Router\Lib\Traits\ArrayTrait;
use Gzhegow\Router\Lib\Traits\ParseTrait;
use Gzhegow\Router\Lib\Traits\DebugTrait;
use Gzhegow\Router\Lib\Traits\AssertTrait;


class Lib
{
    use ArrayTrait;
    use AssertTrait;
    use DebugTrait;
    use OsTrait;
    use ParseTrait;
    use PhpTrait;
}
