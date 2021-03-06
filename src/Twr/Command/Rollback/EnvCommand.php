<?php

namespace Twr\Command\Rollback;

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
            ->setName('rollback:env')
            ->setDescription('Rollback the env locally by running all the specified commands')
            ->addArgument(
                'env',
                InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                'The envs to deploy (if none, deploys all defined)'
            )
            ->addOption(
                'continue',
                null,
                InputOption::VALUE_NONE,
                'Continue the rollback of others envs if one fails'
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
                        '<info>Starting to rollback the environment "<fg=cyan>%s</fg=cyan>"</info>',
                        $env->getName()
                    ));
                }

                try {

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
                                    $output->write(sprintf(
                                        '<error>%s</error>',
                                        $buffer
                                    ));
                                } else {
                                    $output->write(sprintf(
                                        '<fg=cyan>%s</fg=cyan>',
                                        $buffer
                                    ));
                                }
                            });
                    }

                    if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                        $output->writeln(sprintf(
                            '<info>Environment "<fg=cyan>%s</fg=cyan>" rollbacked successfully!</info>',
                            $env->getName()
                        ));
                    }

                } catch (\RuntimeException $e) {

                    $output->writeln(sprintf(
                        '<error>%s</error>',
                        $e->getMessage()
                    ));

                    $this->container
                        ->get('mailer')
                        ->send(
                            '"'.$env->getName().'" failed to rollback',
                            $e->getMessage()
                        );

                    if (!$input->getOption('continue')) {
                        throw $e;
                    }

                }
            } else {
                $output->writeln(sprintf(
                    '<error>Environment "<fg=cyan>%s</fg=cyan>" not found',
                    $env
                ));
            }
        }
    }
}
