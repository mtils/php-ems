<?php
/**
 *  * Created by mtils on 05.11.2021 at 09:37.
 **/

namespace Ems\Model\Schema;

use Ems\Contracts\Core\Configurable;
use Ems\Contracts\Core\HasMethodHooks;
use Ems\Contracts\Model\Schema\MigrationRunner;
use Ems\Contracts\Model\Schema\MigrationStep;
use Ems\Contracts\Model\Schema\MigrationStepRepository;
use Ems\Contracts\Model\Schema\Migrator as MigratorContract;
use Ems\Core\ConfigurableTrait;
use Ems\Core\Exceptions\UnConfiguredException;
use Ems\Core\Patterns\HookableTrait;
use Ems\Core\Url;

use function array_reverse;
use function array_slice;
use function in_array;
use function max;

/**
 *
 */
class Migrator implements MigratorContract, Configurable, HasMethodHooks
{
    use ConfigurableTrait {
        ConfigurableTrait::setOption as traitSetOption;
    }
    use HookableTrait;

    protected $defaultOptions = [
        MigratorContract::PATHS => [],
        MigratorContract::REPOSITORY_URL => null
    ];

    /**
     * @var MigrationStepRepository
     */
    private $repository;

    /**
     * @var MigrationRunner
     */
    private $runner;


    public function __construct(MigrationStepRepository $repository, MigrationRunner $runner)
    {
        $this->defaultOptions[MigratorContract::REPOSITORY_URL] = new Url();
        $this->repository = $repository;
        $this->runner = $runner;
        $this->installRunner($runner);
        $this->configureRepository($repository, true);
    }

    /**
     * @param bool $onePerBatch
     * @param bool $simulate
     *
     * @return MigrationStep[]
     */
    public function migrate(bool $onePerBatch = false, bool $simulate = false): array
    {
        $this->checkConfiguration();
        $all = $this->repository->all();

        if (!$pending = $this->getPending($all)) {
            return [];
        }
        $batch = $this->nextBatchNumber($all);

        return $this->runMigrations($pending, 'upgrade', $simulate, $batch, $onePerBatch);

    }

    /**
     * @param int $count
     * @param bool $simulate
     *
     * @return MigrationStep[]
     */
    public function rollback(int $count = 0, bool $simulate = false): array
    {
        $this->checkConfiguration();
        $all = array_reverse($this->repository->all());

        if (!$toBeRolledBack = $this->getForRollback($all, $count)) {
            return [];
        }

        return $this->runMigrations($toBeRolledBack, 'downgrade', $simulate);

    }

    /**
     * @return MigrationStep[]
     */
    public function migrations(): array
    {
        $this->checkConfiguration();
        return $this->repository->all();
    }

    /**
     * @return string[]
     */
    public function methodHooks()
    {
        $hooks = ['upgrade', 'downgrade'];
        if ($this->supportsQueryHook($this->runner)) {
            $hooks[] = 'query';
        }
        return $hooks;
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return $this
     */
    public function setOption($key, $value)
    {
        $this->traitSetOption($key, $value);
        $this->configureRepository($this->repository, false);
        return $this;
    }


    /**
     * @param array $migrations
     * @param string $method
     * @param bool $simulate
     * @param int $batch
     * @param bool $onePerBatch
     *
     * @return MigrationStep[]
     */
    protected function runMigrations(array $migrations, string $method, bool $simulate, int $batch=0, bool $onePerBatch=false) : array
    {

        $performed = [];
        $isUpgrade = $method == 'upgrade';

        foreach ($migrations as $step) {
            $eventArgs = $isUpgrade ? [$step, $batch, $simulate] : [$step, $simulate];

            $this->callBeforeListeners($method, $eventArgs);

            if ($isUpgrade) {
                $this->runner->upgrade($step->file, $simulate);
            } else {
                $this->runner->downgrade($step->file, $simulate);
            }

            $step->batch = $batch;
            $step->migrated = $isUpgrade;

            $performed[] = $step;

            if (!$simulate) {
                $this->repository->save($step);
            }

            $this->callAfterListeners($method, $eventArgs);

            if ($isUpgrade && $onePerBatch) {
                $batch++;
            }

        }

        return $performed;
    }

    protected function checkConfiguration()
    {
        if (!$this->getOption(MigratorContract::PATHS) || !$this->getOption(MigratorContract::REPOSITORY_URL)) {
            throw new UnConfiguredException("Set paths and repository url");
        }
    }

    /**
     *
     * @return MigrationStep[]
     */
    protected function getPending(array $all) : array
    {
        return array_filter($all, function (MigrationStep $step) {
            return !$step->migrated;
        });
    }

    /**
     * @param MigrationStep[] $reversed
     * @param int $count
     *
     * @return MigrationStep[]
     */
    protected function getForRollback(array $reversed, int $count=0) : array
    {
        if ($count) {
            return array_slice($reversed, 0, $count);
        }

        $lastBatchNumber = $this->lastBatchNumber($reversed);
        $lastBatch = [];
        foreach ($reversed as $step) {
            if ($step->batch == $lastBatchNumber) {
                $lastBatch[] = $step;
            }
        }
        return $lastBatch;
    }

    /**
     * @param MigrationStep[] $all
     *
     * @return int
     */
    protected function lastBatchNumber(array $all) : int
    {
        $maxBatch = 0;
        foreach ($all as $step) {
            $maxBatch = max($step->batch, $maxBatch);
        }
        return (int)$maxBatch;
    }

    /**
     * @param MigrationStep[] $all
     *
     * @return int
     */
    protected function nextBatchNumber(array $all) : int
    {
        return $this->lastBatchNumber($all)+1;
    }

    protected function configureRepository(MigrationStepRepository $repository, bool $initial)
    {
        if (!$repository instanceof Configurable) {
            return;
        }
        if (!$paths = $this->getOption(MigratorContract::PATHS)) {
            return;
        }
        if (!in_array(MigratorContract::PATHS, $repository->supportedOptions())) {
            return;
        }
        $repository->setOption(MigratorContract::PATHS, $paths);

    }

    protected function installRunner(MigrationRunner $runner)
    {
        if (!$this->supportsQueryHook($runner)) {
            return;
        }
        /** @var HasMethodHooks $runner */
        $runner->onBefore('query', function ($query) {
            $this->callBeforeListeners('query', [$query]);
        });
        $runner->onAfter('query', function ($query) {
            $this->callAfterListeners('query', [$query]);
        });
    }

    /**
     * @param MigrationRunner $runner
     * @return bool
     */
    protected function supportsQueryHook(MigrationRunner $runner) : bool
    {
        return $runner instanceof HasMethodHooks && in_array('query', $runner->methodHooks());
    }

}