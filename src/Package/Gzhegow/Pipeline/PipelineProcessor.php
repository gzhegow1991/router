<?php

namespace Gzhegow\Router\Package\Gzhegow\Pipeline;

use Gzhegow\Pipeline\PipelineProcessor as PipelineProcessorBase;


class PipelineProcessor extends PipelineProcessorBase implements
    PipelineProcessorInterface
{
    public function __construct(PipelineFactoryInterface $factory)
    {
        parent::__construct($factory);
    }
}
