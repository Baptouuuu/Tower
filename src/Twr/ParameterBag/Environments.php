<?php

namespace Twr\ParameterBag;

use Twr\Environment as Env;

/**
 * Holds all the environments
 */

class Environments
{
    protected $envs = [];

    /**
     * Set all the environments
     *
     * @param array $envs
     */

    public function setAll(array $envs)
    {
        foreach ($envs as $name => $env) {
            $this->set(
                $name,
                $env
            );
        }
    }

    /**
     * Set an env
     *
     * @param string $name
     * @param array $config
     */

    public function set($name, array $config)
    {
        $env = new Env();

        $env
            ->setName($name)
            ->setPath($config['path']);

        if (isset($config['exports'])) {
            $env->setExports($config['exports']);
        }

        if (isset($config['commands'])) {
            $env->setCommands($config['commands']);
        }

        if (isset($config['rollback'])) {
            $env->setRollback($config['rollback']);
        }

        $this->envs[$name] = $env;
    }

    /**
     * Return the environment object
     *
     * @param string $name
     *
     * @return Environment
     */

    public function get($name)
    {
        return $this->envs[$name];
    }

    /**
     * Return all the environments
     *
     * @return array
     */

    public function getAll()
    {
        return $this->envs;
    }

    /**
     * Check if the environment exist
     *
     * @param string $name
     *
     * @return bool
     */

    public function has($name)
    {
        return isset($this->envs[$name]);
    }
}