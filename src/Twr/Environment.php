<?php

namespace Twr;

/**
 * Represent a local environment that can be deployed
 */

class Environment
{
    protected $name;
    protected $path;
    protected $exports = [];
    protected $commands = [];
    protected $rollback = [];

    /**
     * Set the environment name
     *
     * @param string $name
     *
     * @return Environment self
     */

    public function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Return the environment name
     *
     * @return string
     */

    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the path to the environment
     *
     * @param string $path
     *
     * @return Environment self
     */

    public function setPath($path)
    {
        $this->path = (string) $path;

        return $this;
    }

    /**
     * Return the env path
     *
     * @return string
     */

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set a set of commands to build an env variables
     *
     * @param array $exports
     *
     * @return Environment self
     */

    public function setExports(array $exports)
    {
        $this->exports = $exports;

        return $this;
    }

    /**
     * Return the exports commands
     *
     * @return array
     */

    public function getExports()
    {
        return $this->exports;
    }

    /**
     * Set the set of commands to deploy the env
     *
     * @param array $commands
     *
     * @return Environment self
     */

    public function setCommands(array $commands)
    {
        $this->commands = $commands;

        return $this;
    }

    /**
     * Return the commands
     *
     * @return array
     */

    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Set the commands to rollback the env
     *
     * @param array $commands
     *
     * @return Environment self
     */

    public function setRollback(array $commands)
    {
        $this->rollback = $commands;

        return $this;
    }

    /**
     * return the rollback commands
     *
     * @return array
     */

    public function getRollback()
    {
        return $this->rollback;
    }
}