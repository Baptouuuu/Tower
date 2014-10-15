<?php

namespace Twr;

use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

/**
 * Run a shell command
 */

class CommandRunner
{
    protected $process;
    protected $logger;

    /**
     * Set the logger
     *
     * @param LoggerInterface $logger
     */

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Prepare the next command to run
     *
     * @param string $command
     * @param string $cwd Optional
     * @param array $envs Optional
     *
     * @return CommandRunner self
     */

    public function setCommand($command, $cwd = null, array $envs = null)
    {
        $this->process = new Process($command, $cwd, $envs);

        return $this;
    }

    /**
     * Execute the passed command
     *
     * @throws RuntimeException If the command don't return a successful exit code
     *
     * @param callable $callback Executed on each command output
     *
     * @return int Exit code
     */

    public function run(callable $callback)
    {
        if (!$this->process) {
            throw new \RuntimeException('No command specified');
        }

        $this->logger->info(sprintf(
            'Running command "%s"...',
            $this->process->getCommandLine()
        ));

        $this->process->run(function ($type, $buffer) use ($callback) {
            if ($type === Process::ERR) {
                $this->logger->error($buffer);
            } else {
                $this->logger->info($buffer);
            }

            $callback($type, $buffer);
        });

        $code = $this->process->getExitCode();

        if (!$this->process->isSuccessful()) {
            $msg = sprintf(
                'Command line "%s" failed with the following exit code: %s',
                $this->process->getCommandLine(),
                $code
            );

            $this->logger->emergency($msg, ['exitCode' => $code]);
            $this->process = null;
            throw new \RuntimeException($msg, $code);
        }

        $this->process = null;

        return $code;
    }
}