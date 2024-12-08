<?php

namespace Gzhegow\Router\Package\Gzhegow\Pipeline;

use Gzhegow\Pipeline\PipelineProcessManager as PipelineProcessManagerBase;


class PipelineProcessManager extends PipelineProcessManagerBase implements
    PipelineProcessManagerInterface
{
    public function __construct(
        PipelineFactoryInterface $factory,
        PipelineProcessorInterface $processor
    )
    {
        parent::__construct($factory, $processor);
    }
}
