<?php

namespace Twr\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Process\Process;

class DeployCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('deploy')
            ->setDescription('Deploy the specified childs nodes')
            ->addArgument(
                'child',
                InputArgument::IS_ARRAY,
                'Deploy the specified childs (if none, deploy all of them'
            )
            ->addOption(
                'env',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The envs to deploy on the specified childs (if none, deploy all of them'
            )
            ->addOption(
                'cascade',
                'c',
                InputOption::VALUE_NONE,
                'Whether or not to deploy sub-childs'
            )
            ->addOption(
                'async',
                null,
                InputOption::VALUE_NONE,
                'Whether or not to deploy asynchronously the childs'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->container->getParameter('childs');
        $childs = $input->getArgument('child');
        $logger = $this->container->get('logger');
        $envs = $input->getOption('env');
        $cascade = $input->getOption('cascade');
        $async = $input->getOption('async');

        if (empty($childs)) {
            $childs = array_keys($config);
        }

        foreach ($childs as $child) {
            if (isset($config[$child])) {
                $conf = $config[$child];

                $this->deployChild(
                    $logger,
                    $output,
                    $child,
                    $conf,
                    $envs,
                    $cascade,
                    $async
                );
            }
        }
    }

    /**
     * Run a command to deploy the specified envs to the specified host
     *
     * @param Monolog\Logger $logger
     * @param Symfony\Component\Console\Output\OutputInterface $output
     * @param string $child
     * @param array $conf
     * @param array $envs
     * @param boolean $cascade
     * @param boolean $async
     */
    protected function deployChild($logger, $output, $child, $conf, $envs, $cascade, $async)
    {
        $logger->info(sprintf('Deploying "%s"...', $child));
        $output->writeln(sprintf('<info>Deploying "<fg=cyan>%s</fg=cyan>"...</info>', $child));

        $cmd = sprintf(
            'ssh %s "%s/twr deploy:env %s %s"',
            $conf['host'],
            $conf['path'],
            implode(' ', $envs),
            $async ? '&> /dev/null &' : ''
        );

        $logger->info(sprintf('Running "%s"...', $cmd));
        $output->writeln(sprintf('<info>Running "<fg=cyan>%s</fg=cyan>"...</info>', $cmd));

        $process = new Process($cmd);
        $process->run(function ($type, $buffer) use ($logger, $output) {
            if ($type === 'err') {
                $logger->error($buffer);
                $output->writeln($buffer);
            } else {
                $logger->info($buffer);
                $output->writeln($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new \RuntimeException($msg);
        }
    }
}
