<?php

namespace Ems\Assets\Symfony;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Ems\Contracts\Assets\Builder;
use Ems\Contracts\Assets\BuildConfigRepository;
use Ems\Contracts\Assets\BuildConfig;

/**
 * This command lists all build configurations.
 **/
class CompileCommand extends Command
{
    protected $repo;

    protected $builder;

    public function __construct(BuildConfigRepository $repo, Builder $builder)
    {
        parent::__construct();
        $this->builder = $builder;
        $this->repo = $repo;
    }

    protected function configure()
    {
        $this
        // the name of the command (the part after "bin/console")
        ->setName('assets:compile')

        // the short description shown while running "php bin/console list"
        ->setDescription('Compiles a known build configuration.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp('This command allows you to compile your assets You have to assign a config via BuildConfigRepository::store($config).')

        ->addArgument('group', InputArgument::OPTIONAL, 'The group of configuration.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$groupNames = $this->getGroupNames($input)) {
            $output->writeln('<comment>No build configurations found');

            return;
        }

        $output->writeln([
        'Compiling groups: <comment>'.$this->groupString($groupNames).'</comment>',
        '====================================================================================',
        ]);

        foreach ($groupNames as $groupName) {
            $config = $this->repo->getOrFail($groupName);
            $this->printBuilding($config, $output);
            $compiledPath = $this->builder->build($config);
            $this->printBuilt($config, $compiledPath, $output);
        }
    }

    protected function groupString(array $groupNames)
    {
        return implode(', ', $groupNames);
    }

    protected function getGroupNames(InputInterface $input)
    {
        if (!$groups = $input->getArgument('group')) {
            return $this->repo->groups();
        }

        return array_map(function ($group) {
            return trim($group);
        }, explode(',', $groups));
    }

    protected function printBuilding(BuildConfig $config, OutputInterface $output)
    {
        $output->writeln('Compiling <comment>'.$config->group().' ('.$config->target().')</comment>... ');
    }

    protected function printBuilt(BuildConfig $config, $compiledPath, OutputInterface $output)
    {
        $output->writeln('<info>Successfully written to</info> '.$compiledPath);
    }
}
