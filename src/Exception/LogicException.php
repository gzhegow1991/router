<?php

namespace Gzhegow\Router\Exception;

use function Gzhegow\Router\_php_throw_errors;


class LogicException extends \LogicException
    implements RouterException
{
    public $message;
    public $code;
    public $file;
    public $line;
    public $previous;

    public $messageData;
    public $messageObject;

    public function __construct(...$errors)
    {
        foreach ( _php_throw_errors(...$errors) as $k => $v ) {
            $this->{$k} = $v;
        }

        parent::__construct($this->message, $this->code, $this->previous);
    }
}
