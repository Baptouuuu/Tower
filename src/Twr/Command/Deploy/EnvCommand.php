<?php

namespace Twr\Command\Deploy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Process\Process;

class EnvCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('deploy:env')
            ->setDescription('Deploy the env locally by running the all the specified commands')
            ->addArgument(
                'env',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'The envs to deploy (if none, deploys all defined)'
            )
            ->addOption(
                'continue',
                null,
                InputOption::VALUE_NONE,
                'Continue the deployment of others envs if one fails'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = $this->container->getParameter('envs');
        $envs = $input->getArgument('env');
        $logger = $this->container->get('logger');

        if (empty($envs)) {
            $envs = array_keys($config);
        }

        foreach ($envs as $env) {
            if (isset($config[$env])) {
                $conf = $config[$env];
                $msg = sprintf('Starting to deploy the environment "%s"', $env);
                $logger->info($msg);
                $output->writeln(sprintf('<info>%s</info>', $msg));

                try {

                    foreach ($conf['commands'] as $cmd) {
                        $this->runCommand(
                            $logger,
                            $output,
                            $cmd,
                            $conf['path'],
                            $env
                        );
                    }

                    $msg = sprintf('Environment "%s" deployed successfully!', $env);
                    $logger->info($msg);
                    $output->writeln(sprintf('<info>%s</info>', $msg));

                } catch (\RuntimeException $e) {

                    foreach ($conf['rollback'] as $cmd) {
                        $this->runCommand(
                            $logger,
                            $output,
                            $cmd,
                            $conf['path'],
                            $env
                        );
                    }

                    if (!$input->getOption('continue')) {
                        throw $e;
                    }

                }
            }
        }
    }

    /**
     * Run a specific command, bubble the output to the dev console
     *
     * @param Monolog\Logger $logger
     * @param Symfony\Component\Console\Output\OutputInterface $output
     * @param string $cmd
     * @param string $path
     * @param string $env
     */
    protected function runCommand($logger, $output, $cmd, $path, $env)
    {
        if (preg_match('/^%.+%$/i', $cmd)) {
            $this->runMacro(
                $logger,
                $output,
                $cmd,
                $path,
                $env
            );
            return;
        }

        $msg = sprintf('Running command "%s"', $cmd);
        $logger->info($msg, [$env]);
        $output->writeln(sprintf('<comment>%s</comment>', $msg));

        $process = new Process($cmd, $path);
        $process->run(function ($type, $buffer) use ($logger, $output) {
            if ($type === 'err') {
                $logger->error($buffer);
                $output->write(sprintf('<error>%s</error>', $buffer));
            } else {
                $logger->info($buffer);
                $output->write(sprintf('<fg=cyan>%s</fg=cyan>', $buffer));
            }
        });

        if (!$process->isSuccessful()) {
            $msg = sprintf('Command "%s" failure, attempt to rollback', $cmd);
            $logger->emergency($msg);
            $output->writeln(sprintf('<error>%s</error>', $msg));
            throw new \RuntimeException($msg);
        }
    }

    /**
     * Run a set of commands
     *
     * @param Monolog\Logger $logger
     * @param Symfony\Component\Console\Output\OutputInterface $output
     * @param string $macro
     * @param string $path
     * @param string $env
     */
    protected function runMacro($logger, $output, $macro, $path, $env)
    {
        $macros = $this->container->getParameter('macros');
        $macro = substr($macro, 1, -1);

        if (!isset($macros, $macro)) {
            $msg = sprintf('Macro "%s" not found', $macro);
            $logger->error($msg);
            $output->writeln(sprintf('<error>%s</error>', $msg));
            throw new \RuntimeException($msg);
        }

        $cmds = $macros[$macro];

        foreach ($cmds as $cmd) {
            $this->runCommand(
                $logger,
                $output,
                $cmd,
                $path,
                $env
            );
        }
    }
}
