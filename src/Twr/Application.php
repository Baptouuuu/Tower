<?php

namespace Twr;

use Symfony\Component\Console\Application as Console;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class Application
{
    protected $dir;
    protected $console;
    protected $container;

    public function __construct($dir)
    {
        $this->dir = $dir;
        $this->console = new Console('Tower', '1.2.0');
        $this->container = new ContainerBuilder();
        $loader = new YamlFileLoader($this->container, new FileLocator($this->dir.'/config'));
        $loader->load('services.yml');

        $this->container->setParameter('root_dir', $this->dir);

        $this->loadConfig();

        $this->container->compile();
    }

    /**
     * Loads the node childs + envs from the config.yml
     * and inject the data inside the container
     *
     * @return Twr\Application
     */
    protected function loadConfig()
    {
        try {
            if (file_exists($this->dir.'/tower.yml')) {
                $file = $this->dir.'/tower.yml';
            } else {
                $file = $this->dir.'/config/config.yml';
            }

            $this->container->setParameter('config_path', $file);

            $config = Yaml::parse($file);
            $processor = new Processor();
            $configuration = new Configuration();
            $config = $processor->processConfiguration(
                $configuration,
                [$config]
            );
        } catch (\Exception $e) {
            echo 'Invalid config.yml, please verify your syntax'.PHP_EOL;
            exit(1);
        }

        $this->container->setParameter('envs', $config['envs']);
        $this->container->setParameter('childs', $config['childs']);
        $this->container->setParameter('macros', $config['macros']);
        $this->container->setParameter('log_path', $config['log_path']);

        return $this;
    }

    /**
     * Looks for all *Command.php files to load all commands
     *
     * @return Twr\Application
     */
    public function loadCommands()
    {
        $services = $this->container->findTaggedServiceIds('command');

        foreach ($services as $id => $attributes) {
            $command = $this->container->get($id);
            $refl = new \ReflectionObject($command);

            if ($refl->implementsInterface('Symfony\Component\DependencyInjection\ContainerAwareInterface')) {
                $command->setContainer($this->container);
            }

            $this->console->add($command);
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