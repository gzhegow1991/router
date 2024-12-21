<?php

namespace Gzhegow\Router\Package\Gzhegow\Pipeline\Processor;

use Gzhegow\Pipeline\Processor\PipelineProcessor as ProcessorBase;
use Gzhegow\Router\Package\Gzhegow\Pipeline\RouterPipelineFactoryInterface;


class RouterProcessor extends ProcessorBase implements
    RouterProcessorInterface
{
    public function __construct(RouterPipelineFactoryInterface $factory)
    {
        parent::__construct($factory);
    }
}
