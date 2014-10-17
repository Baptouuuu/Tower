<?php

namespace Twr;

/**
 * Help to build remote commands (to deploy childs)
 */
class CommandBuilder
{
    protected $command;
    protected $args = [];
    protected $async = false;
    protected $host;
    protected $path;

    /**
     * Set the remote tower command to execute
     *
     * @param string $command
     *
     * @return CommandBuilder self
     */

    public function setCommand($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Set all arguments
     *
     * @param array $args
     *
     * @return CommandBuilder self
     */

    public function setArguments(array $args)
    {
        $this->args = $args;

        return $this;
    }

    /**
     * Set the command to run in the background
     *
     * @return CommandBuilder self
     */

    public function setAsync()
    {
        $this->async = true;

        return $this;
    }

    /**
     * Set the command to run in the foreground
     *
     * @return CommandBuilder self
     */

    public function setSync()
    {
        $this->async = false;

        return $this;
    }

    /**
     * Set the target host
     *
     * @param string $host
     *
     * @return CommandBuilder self
     */

    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set the path where is located the tower config on remote host
     *
     * @param string $path
     *
     * @return CommandBuilder self
     */

    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Remove all previously set configuration
     *
     * @return CommandBuilder self
     */

    public function reset()
    {
        $this->command = null;
        $this->args = [];
        $this->async = false;
        $this->host = null;
        $this->path = null;

        return $this;
    }

    /**
     * Build the shell command to launch a remote tower command
     *
     * @return string
     */

    public function getCommandLine()
    {
        $cmd = 'cd '.$this->path.' && ';
        $cmd .= 'twr '.$this->command.' ';

        $cmd .= implode(' ', $this->args);

        if ($this->async === true) {
            $cmd .= ' &> /dev/null & echo $!';
        }

        $cmd = sprintf(
            'ssh -C -t -t %s \'%s\'',
            $this->host,
            $cmd
        );

        if ($this->async === true) {
            $cmd .= ' | { read PID; echo Deployment PID: $PID; }';
        }

        $this->reset();

        return $cmd;
    }
}