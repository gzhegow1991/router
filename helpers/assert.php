<?php

namespace Gzhegow\Router;


function _err($err, $result = null)
{
    $messageObject = is_array($err)
        ? (object) $err
        : (object) [ $err ];

    trigger_error($messageObject->{0}, E_USER_NOTICE);

    return $result;
}


function _filter_str($value) : ?string
{
    if (is_string($value)) {
        return $value;
    }

    if (
        (null === $value)
        || is_array($value)
        || is_resource($value)
    ) {
        return null;
    }

    if (is_object($value)) {
        if (method_exists($value, '__toString')) {
            $_value = (string) $value;

            return $_value;
        }

        return null;
    }

    $_value = $value;
    $status = @settype($_value, 'string');

    if ($status) {
        return $_value;
    }

    return null;
}

function _filter_string($value) : ?string
{
    if (null === ($_value = _filter_str($value))) {
        return null;
    }

    if ('' === $_value) {
        return null;
    }

    return $_value;
}


function _filter_path(
    $value, array $optional = [],
    array &$pathinfo = null
) : ?string
{
    $pathinfo = null;

    $optional[ 0 ] = $optional[ 'with_pathinfo' ] ?? $optional[ 0 ] ?? false;

    if (null === ($_value = _filter_string($value))) {
        return null;
    }

    if (false !== strpos($_value, "\0")) {
        return null;
    }

    $withPathInfoResult = (bool) $optional[ 0 ];

    if ($withPathInfoResult) {
        try {
            $pathinfo = pathinfo($_value);
        }
        catch ( \Throwable $e ) {
            return null;
        }
    }

    return $_value;
}

function _filter_dirpath(
    $value, array $optional = [],
    array &$pathinfo = null
) : ?string
{
    $_value = _filter_path(
        $value, $optional,
        $pathinfo
    );

    if (null === $_value) {
        return null;
    }

    if (file_exists($_value) && ! is_dir($_value)) {
        return null;
    }

    return $_value;
}


function _filter_filename($value) : ?string
{
    if (null === ($_value = _filter_string($value))) {
        return null;
    }

    $forbidden = [ "\0", "/", "\\", DIRECTORY_SEPARATOR ];

    foreach ( $forbidden as $f ) {
        if (false !== strpos($_value, $f)) {
            return null;
        }
    }

    return $_value;
}


function _filter_regex($regex) : ?string
{
    if (null === ($_value = _filter_string($regex))) {
        return null;
    }

    $before = error_reporting(0);
    $status = @preg_match($regex, '');
    error_reporting($before);

    if (false === $status) {
        return null;
    }

    return $_value;
}


/**
 * @param callable ...$fnExistsList
 */
function _filter_struct($value, bool $useRegex = null, ...$fnExistsList) : ?string
{
    $useRegex = $useRegex ?? false;
    $fnExistsList = $fnExistsList ?: [ 'class_exists' ];

    if (is_object($value)) {
        return ltrim(get_class($value), '\\');
    }

    if (null === ($_value = _filter_string($value))) {
        return null;
    }

    $_value = ltrim($_value, '\\');

    foreach ( $fnExistsList as $fn ) {
        if ($fn($_value)) {
            return $_value;
        }
    }

    if ($useRegex) {
        if (! preg_match(
            '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\\[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)*$/',
            $_value
        )) {
            return null;
        }
    }

    return $_value;
}

function _filter_class($value, bool $useRegex = null) : ?string
{
    $_value = _filter_struct($value, $useRegex, 'class_exists');

    if (null === $_value) {
        return null;
    }

    return $_value;
}
