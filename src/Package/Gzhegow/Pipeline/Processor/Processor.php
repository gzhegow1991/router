<?php

namespace Gzhegow\Router\Package\Gzhegow\Pipeline\Processor;

use Gzhegow\Pipeline\Processor\Processor as ProcessorBase;
use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineFactoryInterface;


class Processor extends ProcessorBase implements
    ProcessorInterface
{
    public function __construct(PipelineFactoryInterface $factory)
    {
        parent::__construct($factory);
    }
}
