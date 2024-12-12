<?php

namespace Gzhegow\Router\Package\Gzhegow\Pipeline\ProcessManager;

use Gzhegow\Router\Package\Gzhegow\Pipeline\PipelineFactoryInterface;
use Gzhegow\Pipeline\ProcessManager\ProcessManager as ProcessManagerBase;
use Gzhegow\Router\Package\Gzhegow\Pipeline\Processor\ProcessorInterface;


class ProcessManager extends ProcessManagerBase implements
    ProcessManagerInterface
{
    public function __construct(
        PipelineFactoryInterface $factory,
        //
        ProcessorInterface $processor
    )
    {
        parent::__construct($factory, $processor);
    }
}
