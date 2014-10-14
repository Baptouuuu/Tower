<?php

namespace Twr;

use Twr\ParameterBag\Environments as Envs;
use Twr\Exceptions\EnvironmentNotFoundException;
use Twr\Exceptions\InvalidExportFormatException;
use Symfony\Component\Process\Process;

/**
 * Build an array of environment variables to be used on each command
 */

class ExportsResolver
{
    protected $envs;

    /**
     * Set the environment variables
     *
     * @param Environments $envs
     */

    public function setEnvs(Envs $envs)
    {
        $this->envs = $envs;
    }

    /**
     * Return all the env variables
     *
     * @param string $name Environment name
     *
     * @return array
     */

    public function getExports($name)
    {
        if (!$this->envs->has($name)) {
            throw new EnvironmentNotFoundException($name);
        }

        $env = $this->envs->get($name);
        $exports = $env->getExports();
        $data = [];

        foreach ($exports as $export) {
            $process = new Process(
                $export,
                $env->getPath(),
                $data
            );
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }

            list($key, $value) = $this->processOutput($process->getOutput());

            $data[$key] = $value;
        }

        return $data;
    }

    /**
     * Extract from the string the string key and its value
     *
     * @param string $output
     *
     * @return array
     */

    protected function processOutput($output)
    {
        preg_match('/(?P<key>[A-Z_]+)=(?P<value>.*)/', $output, $matches);

        if (empty($matches)) {
            throw new InvalidExportFormatException($output);
        }

        return [$matches['key'], $matches['value']];
    }
}