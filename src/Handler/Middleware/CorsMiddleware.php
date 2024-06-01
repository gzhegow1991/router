<?php

namespace Gzhegow\Router\Handler\Middleware;

class CorsMiddleware
{
    public function __invoke($fnNext, $input, $context)
    {
        $result = $fnNext($input, $context);

        if (isset($_SERVER[ 'HTTP_ORIGIN' ])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        return $result;
    }
}
