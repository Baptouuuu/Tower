<?php

namespace Twr\ParameterBag;

use Twr\Child;

class Childs
{
    protected $childs = [];

    /**
     * Set all the childs at once
     *
     * @param array $childs
     */

    public function setAll(array $childs)
    {
        foreach ($childs as $name => $child) {
            $this->set(
                $name,
                $child
            );
        }
    }

    /**
     * Set a child config
     *
     * @param string $name
     * @param array $config
     */

    public function set($name, array $config)
    {
        $child = new Child();

        $child
            ->setName($name)
            ->setHost($config['host'])
            ->setPath($config['path']);

        $this->childs[$name] = $child;
    }

    /**
     * Return a child object
     *
     * @param string $name
     *
     * @return Child
     */

    public function get($name)
    {
        return $this->childs[$name];
    }

    /**
     * Return all the childs
     *
     * @return array
     */

    public function getAll()
    {
        return $this->childs;
    }

    /**
     * Check if the child exist
     *
     * @param string $name
     *
     * @return bool
     */

    public function has($name)
    {
        return isset($this->childs[$name]);
    }
}