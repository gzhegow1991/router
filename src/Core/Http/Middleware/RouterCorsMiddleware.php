<?php

namespace Gzhegow\Router\Core\Http\Middleware;

class RouterCorsMiddleware
{
    public function __invoke(
        $fnNext,
        $input,
        array $context = [],
        array $args = []
    )
    {
        $result = $fnNext($input, $args);

        if (isset($_SERVER[ 'REQUEST_METHOD' ])) {
            if ('OPTIONS' === strtoupper($_SERVER[ 'REQUEST_METHOD' ])) {
                if (isset($_SERVER[ 'HTTP_ACCESS_CONTROL_REQUEST_METHOD' ])) {
                    header("Access-Control-Allow-Methods: OPTIONS, {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']}");
                }

                if (isset($_SERVER[ 'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' ])) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                    header("Access-Control-Expose-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
            }
        }

        if (isset($_SERVER[ 'HTTP_ORIGIN' ])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        return $result;
    }
}
