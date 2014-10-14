<?php

namespace Twr;

use Twr\Exceptions\MacroNotFoundException;
use Twr\ParameterBag\Macros;

/**
 * Look at a shell command and if it's a shorcut it will resolve all
 * the underlying commands
 */

class CommandResolver
{
    const PATTERN = '/^%.+%$/i';
    protected $macros;

    /**
     * Set all the defined macros
     *
     * @param Macros $macros
     */

    public function setMacros(Macros $macros)
    {
        $this->macros = $macros;
    }

    /**
     * If the given command is a macro it will resolve all the commands
     * otherwise it return the same command but in an array
     *
     * @param string $command
     *
     * @return array
     */

    public function resolve ($command)
    {
        if (!preg_match(self::PATTERN, $command)) {
            return [$command];
        }

        $commands = [];
        $macro = substr($command, 1, -1);

        if (!$this->macros->has($macro)) {
            throw new MacroNotFoundException($macro);
        }

        foreach ($this->macros->get($macro) as $cmd) {
            $commands = array_merge(
                $commands,
                $this->resolve($cmd)
            );
        }

        return $commands;
    }
}