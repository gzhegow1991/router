<?php

namespace Gzhegow\Router\Lib\Traits;

trait ParseTrait
{
    public static function parse_string($value) : ?string
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

    public static function parse_astring($value) : ?string
    {
        if (null === ($_value = static::parse_string($value))) {
            return null;
        }

        if ('' === $_value) {
            return null;
        }

        return $_value;
    }


    public static function parse_path(
        $value, array $optional = [],
        array &$pathinfo = null
    ) : ?string
    {
        $pathinfo = null;

        $optional[ 0 ] = $optional[ 'with_pathinfo' ] ?? $optional[ 0 ] ?? false;

        if (null === ($_value = static::parse_astring($value))) {
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

    public static function parse_dirpath(
        $value, array $optional = [],
        array &$pathinfo = null
    ) : ?string
    {
        $_value = static::parse_path(
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


    public static function parse_filename($value) : ?string
    {
        if (null === ($_value = static::parse_astring($value))) {
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


    public static function parse_regex($regex) : ?string
    {
        if (null === ($_value = static::parse_astring($regex))) {
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
}
