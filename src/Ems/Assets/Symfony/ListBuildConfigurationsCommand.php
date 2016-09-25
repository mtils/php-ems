<?php


namespace Ems\Assets\Symfony;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Ems\Contracts\Assets\BuildConfigRepository;
use Ems\Contracts\Assets\Registry;
use Ems\Contracts\Core\Filesystem;

/**
 * This command lists all build configurations
 *
 **/
class ListBuildConfigurationsCommand extends Command
{

    protected $repo;

    protected $files;

    protected $registry;

    protected $headers = [
        'group'         => 'Group',
        'files'         => 'Includes',
//         'base_path'     => 'Base path',
        'target'        => 'Compiled File',
        'parserNames'   => 'Parsers',
        'target_exists' => 'Compiled?'
    ];

    public function __construct(BuildConfigRepository $repo, Filesystem $files, Registry $registry)
    {
        parent::__construct();
        $this->repo = $repo;
        $this->files = $files;
        $this->registry = $registry;
    }

    protected function configure()
    {
        $this
        // the name of the command (the part after "bin/console")
        ->setName('assets:builds')

        // the short description shown while running "php bin/console list"
        ->setDescription('Lists all available build configurations.')

        // the full command description shown when running the command with
        // the "--help" option
        ->setHelp("This command allows you to list the assigned asset build configurations.");

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $rows = $this->formatForTable($this->collectConfigurations());

        $table = new Table($output);

        $table->setHeaders(array_values($this->headers))
              ->setRows($rows)
              ->setStyle('default')
              ->render();

    }

    protected function formatForTable($configurations)
    {
        $rows = [];
        foreach ($configurations as $config) {
            $row = [];
            foreach ($this->headers as $key=>$title) {

                if ($key == 'files') {
                    $row[] = $this->formatCollection($config->collection());
                    continue;
                }

                if ($key == 'parserNames') {
                    $row[] = implode(',', $config->parserNames());
                    continue;
                }

                if ($key == 'target_exists') {
                    $row[] = $this->targetExists($config) ? '<info>Yes</info>' : '<error>No</error>';
                    continue;
                }

                if ($key == 'base_path') {
                    $row[] = $this->basePath($config);
                    continue;
                }

                $row[] = call_user_func([$config, $key]);

            }
            $rows[] = $row;
        }
        return $rows;
    }

    protected function formatCollection($collection)
    {
        $assetNames = $collection->apply(function($asset) { return $asset->name(); });
        return implode("\n", $assetNames);
    }

    protected function targetExists($config)
    {
        $absolutePath = $this->registry->to($config->group())->absolute($config->target());
        return $this->files->exists($absolutePath);
    }

    protected function basePath($config)
    {
        return $this->registry->to($config->group())->absolute();
    }

    protected function collectConfigurations()
    {
        $configurations = [];

        foreach ($this->repo->groups() as $group) {
            $configurations[] = $this->repo->get($group);
        }

        return $configurations;
    }

}
