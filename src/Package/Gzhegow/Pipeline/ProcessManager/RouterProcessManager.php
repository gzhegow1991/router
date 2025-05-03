<?php

namespace Gzhegow\Router\Package\Gzhegow\Pipeline\ProcessManager;

use Gzhegow\Router\Package\Gzhegow\Pipeline\RouterPipelineFactoryInterface;
use Gzhegow\Router\Package\Gzhegow\Pipeline\Processor\RouterProcessorInterface;
use Gzhegow\Pipeline\Core\ProcessManager\PipelineProcessManager as ProcessManagerBase;


class RouterProcessManager extends ProcessManagerBase implements
    RouterProcessManagerInterface
{
    public function __construct(
        RouterPipelineFactoryInterface $factory,
        //
        RouterProcessorInterface $processor
    )
    {
        parent::__construct($factory, $processor);
    }
}
