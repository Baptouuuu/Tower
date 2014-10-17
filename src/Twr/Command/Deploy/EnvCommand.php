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
        $bag = $this->container->get('envs_bag');
        $resolver = $this->container->get('command_resolver');
        $exports_resolver = $this->container->get('exports_resolver');
        $runner = $this->container->get('runner');
        $envs = $input->getArgument('env');

        if (empty($envs)) {
            $envs = array_keys($bag->getAll());
        }

        foreach ($envs as $env) {
            if ($bag->has($env)) {
                $env = $bag->get($env);
                $exports = $exports_resolver->getExports(
                    $env->getName()
                );

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                    $output->writeln(sprintf(
                        '<info>Starting to deploy the environment "%s"</info>',
                        $env->getName()
                    ));
                }

                try {

                    $commands = [];

                    foreach ($env->getCommands() as $cmd) {
                        $commands = array_merge(
                            $commands,
                            $resolver->resolve($cmd)
                        );
                    }

                    foreach ($commands as $cmd) {
                        $runner
                            ->setCommand(
                                $cmd,
                                $env->getPath(),
                                $exports
                            )
                            ->run(function ($type, $buffer) use ($output) {
                                if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
                                    return;
                                }

                                if ($type === Process::ERR) {
                                    $output->writeln(sprintf(
                                        '<error>%s</error>',
                                        $buffer
                                    ));
                                } else {
                                    $output->writeln(sprintf(
                                        '<fg=cyan>%s</fg=cyan>',
                                        $buffer
                                    ));
                                }
                            });
                    }

                    if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                        $output->writeln(sprintf(
                            '<info>Environment "%s" deployed successfully!</info>',
                            $env->getName()
                        ));
                    }

                } catch (\RuntimeException $e) {

                    $output->writeln(sprintf(
                        '<error>%s</error>',
                        $e->getMessage()
                    ));

                    $commands = [];

                    foreach ($env->getRollback() as $cmd) {
                        $commands = array_merge(
                            $commands,
                            $resolver->resolve($cmd)
                        );
                    }

                    foreach ($commands as $cmd) {
                        $runner
                            ->setCommand(
                                $cmd,
                                $env->getPath(),
                                $exports
                            )
                            ->run(function ($type, $buffer) use ($output) {
                                if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
                                    return;
                                }

                                if ($type === Process::ERR) {
                                    $output->writeln(sprintf(
                                        '<error>%s</error>',
                                        $buffer
                                    ));
                                } else {
                                    $output->writeln(sprintf(
                                        '<fg=cyan>%s</fg=cyan>',
                                        $buffer
                                    ));
                                }
                            });
                    }

                    if (!$input->getOption('continue')) {
                        throw $e;
                    }

                }
            } else {
                $output->writeln(sprintf(
                    '<error>Environment "%s" not found',
                    $env
                ));
            }
        }
    }
}
