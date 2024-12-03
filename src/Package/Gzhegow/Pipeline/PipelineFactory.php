<?php

namespace Gzhegow\Router\Package\Gzhegow\Pipeline;

use Gzhegow\Pipeline\PipelineProcessorInterface;
use Gzhegow\Pipeline\PipelineFactory as PipelineFactoryBase;


class PipelineFactory extends PipelineFactoryBase
    implements PipelineFactoryInterface
{
    public function newProcessor() : PipelineProcessorInterface
    {
        $processor = new PipelineProcessor($this);

        return $processor;
    }
}
