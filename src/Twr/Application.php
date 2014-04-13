<?php

namespace Twr;

class Application
{
    protected $dir;

    public function __construct($dir)
    {
        $this->dir = $dir;
    }

    /**
     * Looks for all *Command.php files to load all commands
     *
     * @return Twr\Application
     */
    public function loadCommands()
    {

    }

    /**
     * Run the console tool
     *
     * @return Twr\Application
     */
    public function run()
    {

    }
}