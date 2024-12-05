<?php

namespace Gzhegow\Router\Config;

use Gzhegow\Router\Exception\LogicException;


abstract class AbstractConfig
{
    public function __get($name)
    {
        if (! property_exists($this, $name)) {
            throw new LogicException('Missing property: ' . $name);
        }

        return $this->{$name};
    }

    public function __set($name, $value)
    {
        if (! property_exists($this, $name)) {
            throw new LogicException('Missing property: ' . $name);
        }

        $this->{$name} = $value;
    }


    /**
     * @param self $config
     *
     * @return static
     */
    public function fill($config) // : static
    {
        if (! is_a($config, static::class)) {
            throw new LogicException('The `config` should be instance of: ' . static::class);
        }

        $vars = get_object_vars($config);

        foreach ( $vars as $key => $value ) {
            $this->{$key} = $value;
        }

        return $this;
    }
}
