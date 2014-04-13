<?php

namespace Twr;

use Symfony\Component\Console\Application as Console;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;

class Application
{
    protected $dir;
    protected $console;
    protected $container;

    public function __construct($dir)
    {
        $this->dir = $dir;
        $this->console = new Console();
        $this->container = new ContainerBuilder();
    }

    /**
     * Looks for all *Command.php files to load all commands
     *
     * @return Twr\Application
     */
    public function loadCommands()
    {
        $finder = new Finder();
        $iterator = $finder
            ->files()
            ->depth(0)
            ->name('*Command.php')
            ->in($this->dir);

        foreach($iterator as $file) {
            require_once $file->getRealPath();
        }

        $classes = get_declared_classes();

        foreach ($classes as $class) {
            $refl = new \ReflectionClass($class);

            if ($refl->isSubclassOf('Symfony\Component\Console\Command\Command')) {
                $cmd = $refl->newInstance();

                if ($refl->implementsInterface('Symfony\Component\DependencyInjection\ContainerAwareInterface')) {
                    $cmd->setContainer($this->container);
                }

                $this->console->add($cmd);
            }
        }

        return $this;
    }

    /**
     * Run the console tool
     *
     * @return Twr\Application
     */
    public function run()
    {
        $this->console->run();

        return $this;
    }
}