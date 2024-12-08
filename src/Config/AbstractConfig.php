<?php

namespace Gzhegow\Router\Config;


abstract class AbstractConfig
{
    /**
     * @var AbstractConfig[]
     */
    protected $__sections = [];


    public function __isset($name)
    {
        if (substr($name, 0, 2) === '__') {
            return false;
        }

        if (! property_exists($this, $name)) {
            return false;
        }

        return true;
    }

    public function __get($name)
    {
        if (! $this->__isset($name)) {
            throw new \LogicException(
                'Missing property: ' . $name
            );
        }

        return $this->{$name};
    }

    public function __set($name, $value)
    {
        if (! $this->__isset($name)) {
            throw new \LogicException(
                'Missing property: ' . $name
            );
        }

        $this->{$name} = $value;
    }

    public function __unset($name)
    {
        if (! $this->__isset($name)) {
            throw new \LogicException(
                'Missing property: ' . $name
            );
        }

        $this->{$name} = null;
    }


    /**
     * @param \Closure $fn
     *
     * @return void
     */
    public function configure(\Closure $fn) : void
    {
        $fn->call($this, $this);

        foreach ( $this->__sections as $key => $section ) {
            $current = $this->{$key};

            if ($current === $this->__sections[ $key ]) {
                continue;
            }

            if (false
                || (! is_object($current))
                || (! is_a($current, get_class($section)))
            ) {
                throw new \LogicException(
                    [
                        'Invalid section: ' . $key,
                        $current,
                    ]
                );
            }

            $this->__sections[ $key ]->fill($this->{$key});

            $this->{$key} = $this->__sections[ $key ];
        }
    }


    public function reset() : void
    {
        $this->fill(new static());
    }

    /**
     * @param self $config
     *
     * @return static
     */
    public function fill(self $config) // : static
    {
        $vars = get_object_vars($config);

        foreach ( $vars as $key => $value ) {
            if (! $this->__isset($key)) {
                continue;
            }

            if (array_key_exists($key, $this->__sections)) {
                // ! recursion
                $this->{$key}->fill($value);

            } else {
                $this->{$key} = $value;
            }
        }

        return $this;
    }
}
