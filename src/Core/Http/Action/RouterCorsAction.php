<?php

namespace Gzhegow\Router\Core\Http\Action;


class RouterCorsAction
{
    public function __invoke(
        $input,
        array $context = [],
        array $args = []
    )
    {
        if ( array_key_exists('REQUEST_METHOD', $_SERVER) ) {
            if ( 'OPTIONS' === strtoupper($_SERVER['REQUEST_METHOD']) ) {
                if ( isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']) ) {
                    header("Access-Control-Allow-Methods: OPTIONS, {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']}");
                }

                if ( isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']) ) {
                    header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                    header("Access-Control-Expose-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
                }
            }
        }

        if ( array_key_exists('HTTP_ORIGIN', $_SERVER) ) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Max-Age: 86400');
        }

        http_response_code(200);

        return '';
    }
}
