<?php

namespace Twr;

/**
 * Representation a child server
 */

class Child
{
    protected $name;
    protected $host;
    protected $path;

    /**
     * Set the child name
     *
     * @param string $name
     *
     * @return Child self
     */

    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Return the child name
     *
     * @return string
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the host of the child
     *
     * @param string $host
     *
     * @return Child self
     */

    public function setHost($host)
    {
        $this->host = (string) $host;

        return $this;
    }

    /**
     * Return the host
     *
     * @return string
     */

    public function getHost()
    {
        return $this->host;
    }

    /**
     * Set the path to the tower config
     *
     * @param string $path
     *
     * @return Child self
     */

    public function setPath($path)
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Return the path
     *
     * @return string
     */

    public function getPath()
    {
        return $this->path;
    }
}