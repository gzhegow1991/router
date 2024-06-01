<?php

namespace Gzhegow\Router\Exception;

use Gzhegow\Router\Lib;


class RuntimeException extends \RuntimeException
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
        foreach ( Lib::php_throw_errors(...$errors) as $k => $v ) {
            $this->{$k} = $v;
        }

        parent::__construct($this->message, $this->code, $this->previous);
    }
}
