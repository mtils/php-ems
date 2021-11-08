<?php
/**
 *  * Created by mtils on 07.11.2021 at 16:22.
 **/

namespace Ems\Model\Schema\Illuminate;

use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\Filesystem;
use Ems\Contracts\Model\Schema\MigrationStep;
use Ems\Contracts\Model\Schema\MigrationStepRepository;
use Ems\Contracts\Model\Schema\Migrator as MigratorContract;
use Ems\Core\ConfigurableTrait;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use stdClass;

class IlluminateMigrationStepRepository implements MigrationStepRepository, Configurable
{
    use ConfigurableTrait;

    protected $defaultOptions = [
        MigratorContract::PATHS => []
    ];

    /**
     * @var MigrationRepositoryInterface
     */
    protected $nativeRepository;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var int
     */
    protected $stepLimit = 5000;

    public function __construct(MigrationRepositoryInterface $nativeRepository, Filesystem $fs)
    {
        $this->nativeRepository = $nativeRepository;
        $this->fs = $fs;
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        $files = $this->getMigrationFiles();
        $nativeMigrations = $this->getNativeMigrations();

        $steps = [];
        foreach ($files as $file) {

            $baseFile = $this->fs->basename($file);
            $step = new MigrationStep();
            $step->file = $baseFile;
            if (isset($nativeMigrations[$baseFile])) {
                $step->migrated = true;
            }
            if (isset($nativeMigrations[$baseFile]->batch)) {
                $step->batch = $nativeMigrations[$baseFile]->batch;
            }
            $steps[] = $step;
        }
        return $steps;
    }

    /**
     * @param MigrationStep $step
     * @return bool
     */
    public function save(MigrationStep $step): bool
    {
        if ($step->migrated) {
            $this->nativeRepository->log($step->file, $step->batch);
            return true;
        }

        $migration = new stdClass();
        $migration->migration = $step->file;
        $migration->batch = $step->batch;

        $this->nativeRepository->delete($migration);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function install(): void
    {
        $this->nativeRepository->createRepository();
    }

    /**
     * @return int
     */
    public function getStepLimit(): int
    {
        return $this->stepLimit;
    }

    /**
     * @param int $stepLimit
     */
    public function setStepLimit(int $stepLimit): void
    {
        $this->stepLimit = $stepLimit;
    }


    /**
     * @return string[]
     */
    protected function getMigrationFiles() : array
    {
        $files = [];
        foreach ($this->getOption(MigratorContract::PATHS) as $path) {
            foreach ($this->fs->files($path, '*', 'php') as $file) {
                $files[] = $file;
            }
        }
        sort($files);
        return $files;
    }

    /**
     * @return stdClass[]
     */
    protected function getNativeMigrations() : array
    {
        $migrationByFile = [];
        $migrationEntries = $this->nativeRepository->getMigrations($this->stepLimit);
        foreach ($migrationEntries as $migrationEntry) {
            $migrationByFile[$migrationEntry->migration] = $migrationEntry;
        }
        return $migrationByFile;
    }
}