<?php

namespace Gzhegow\Router\Exception\Exception;

use Gzhegow\Pipeline\Lib;
use Gzhegow\Pipeline\Exception\Exception;
use Gzhegow\Pipeline\Exception\ExceptionInterface;


class DispatchException extends Exception
    implements ExceptionInterface
{
    public $message;
    public $code;
    public $previous;

    public $previousStack = [];


    public function __construct(...$errors)
    {
        foreach ( Lib::php_throwable_args(...$errors) as $k => $v ) {
            if (property_exists($this, $k)) {
                $this->{$k} = $v;
            }
        }

        parent::__construct($this->message, $this->code, $this->previous);
    }


    public function getPreviousStack() : array
    {
        return $this->previousStack;
    }

    public function addPrevious(\Throwable $throwable) : void
    {
        $this->previousStack[] = $throwable;
    }
}
