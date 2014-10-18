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

class RollbackCommand extends Command implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    protected function configure()
    {
        $this
            ->setName('rollback')
            ->setDescription('Rollback the specified childs nodes')
            ->addArgument(
                'child',
                InputArgument::IS_ARRAY,
                'Rollback the specified childs (if none, deploy all of them)'
            )
            ->addOption(
                'env',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The envs to rollback on the specified childs (if none, deploy all of them)'
            )
            ->addOption(
                'cascade',
                'c',
                InputOption::VALUE_NONE,
                'Whether or not to rollback sub-childs'
            )
            ->addOption(
                'async',
                null,
                InputOption::VALUE_NONE,
                'Whether or not to rollback asynchronously the childs'
            )
            ->addOption(
                'tmout',
                't',
                InputOption::VALUE_OPTIONAL,
                'The process timeout in seconds',
                3600
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bag = $this->container->get('childs_bag');
        $builder = $this->container->get('builder');
        $runner = $this->container->get('runner');
        $childs = $input->getArgument('child');
        $envs = $input->getOption('env');
        $cascade = $input->getOption('cascade');
        $async = $input->getOption('async');
        $tmout = $input->getOption('tmout');

        if (empty($childs)) {
            $childs = array_keys($bag->getAll());
        }

        $args = $envs;
        $namedArgs = array_map(function ($env) {
            return '--env='.$env;
        }, $envs);

        $namedArgs[] = '-n';
        $namedArgs[] = '-c';
        $namedArgs[] = sprintf('-t %s', $tmout);

        foreach ($childs as $child) {
            if ($bag->has($child)) {
                $child = $bag->get($child);

                $builder
                    ->setCommand('rollback:env')
                    ->setArguments($args)
                    ->setHost($child->getHost())
                    ->setPath($child->getPath());

                if ($async) {
                    $builder->setAsync();
                }

                $cmd = $builder->getCommandLine();

                if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                    $output->writeln(sprintf(
                        '<info>Attempt to rollback "<fg=cyan>%s</fg=cyan>"...</info>',
                        $child->getName()
                    ));
                    $output->writeln(sprintf(
                        '<info>Running "<fg=cyan>%s</fg=cyan>"...</info>',
                        $cmd
                    ));
                }

                $runner
                    ->setCommand($cmd)
                    ->setTimeout($tmout)
                    ->run(function ($type, $buffer) use ($output) {
                        if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
                            return;
                        }

                        $output->write($buffer);
                    });

                if ($cascade) {
                    $builder
                        ->setCommand('rollback')
                        ->setArguments($namedArgs)
                        ->setHost($child->getHost())
                        ->setPath($child->getPath());

                    if ($async) {
                        $builder->setAsync();
                    }

                    $cmd = $builder->getCommandLine();

                    if ($output->getVerbosity() > OutputInterface::VERBOSITY_QUIET) {
                        $output->writeln(sprintf(
                            '<info>Running "<fg=cyan>%s</fg=cyan>"...</info>',
                            $cmd
                        ));
                    }

                    $runner
                        ->setCommand($cmd)
                        ->setTimeout($tmout)
                        ->run(function ($type, $buffer) use ($output) {
                            if ($output->getVerbosity() === OutputInterface::VERBOSITY_QUIET) {
                                return;
                            }

                            $output->write($buffer);
                        });
                }
            }
        }
    }
}
