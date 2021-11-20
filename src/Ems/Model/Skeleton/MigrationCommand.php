<?php
/**
 *  * Created by mtils on 20.11.2021 at 08:02.
 **/

namespace Ems\Model\Skeleton;

use Ems\Console\ArgvInput;
use Ems\Console\ConsoleInputConnection;
use Ems\Console\ConsoleOutputConnection;
use Ems\Contracts\Core\InputConnection;
use Ems\Contracts\Core\OutputConnection;
use Ems\Contracts\Model\Exceptions\MigratorInstallationException;
use Ems\Contracts\Model\Schema\MigrationStep;
use Ems\Contracts\Model\Schema\Migrator;

use function basename;
use function function_exists;
use function max;
use function mb_strlen;
use function str_pad;
use function str_repeat;

use const PHP_EOL;
use const STR_PAD_LEFT;

class MigrationCommand
{
    /**
     * @var Migrator
     */
    protected $migrator;

    /**
     * @var InputConnection
     */
    protected $in;

    /**
     * @var OutputConnection
     */
    protected $out;

    public function __construct(Migrator $migrator, InputConnection $in, OutputConnection $out)
    {
        $this->migrator = $migrator;
        $this->in = $in;
        $this->out = $out;
    }

    public function status(ArgvInput $input)
    {
        if (!$migrations = $this->getMigrations()) {
            $this->line('<comment>No migrations found</comment>');
            return '';
        }

        $this->outputMigrations($migrations);

    }

    protected function outputMigrations(array $migrations)
    {
        $longestNameLength = 0;
        foreach ($migrations as $migration) {
            $length = $this->stringLength(basename($migration->file));
            $longestNameLength = max($length, $longestNameLength);
        }

        $hl     = '+-----------' . str_repeat('-', $longestNameLength-10) . '-+-------+' ;
        $header = '| <comment>Migration</comment> ' . str_repeat(' ', $longestNameLength-10) . ' | <comment>Batch</comment> |' ;
        $this->line($hl);
        $this->line($header);
        $this->line($hl);
        foreach ($migrations as $migration) {
            $tag = $migration->migrated ? 'info' : 'mute';
            $line = "| <$tag>" . str_pad(basename($migration->file), $longestNameLength) . "</$tag> ";

            if ($migration->batch) {
                $line .= '| ' . str_pad((string)$migration->batch, 5, ' ', STR_PAD_LEFT) . ' |';
            } else {
                $line .= '|   <mute>-</mute>   |';
            }

            $this->line($line);
        }
        $this->line($hl);
    }

    protected function letInstall() : bool
    {
        if (!$this->in instanceof ConsoleInputConnection) {
            $this->migrator->install();
            return true;
        }
        $this->line('<info>Want to install migration repository now?</info>');
        if (!$this->in->confirm()) {
            $this->line('<warning>Aborted</warning>');
            return false;
        }
        $this->migrator->install();
        return true;
    }

    /**
     * @return MigrationStep[]
     */
    protected function getMigrations() : array
    {
        try {
            return $this->migrator->migrations();
        } catch (MigratorInstallationException $e) {
            if ($e->getCode() == MigratorInstallationException::NOT_INSTALLABLE) {
                throw $e;
            }
        }
        $this->line('<error>Migration repository is not installed</error>');
        if (!$this->letInstall()) {
            return [];
        }
        return $this->getMigrations();
    }

    protected function line($string, $formatted=null, $newLine=PHP_EOL)
    {
        if ($this->out instanceof ConsoleOutputConnection) {
            $this->out->line($string, $formatted, $newLine);
        }
    }

    protected function stringLength($file)
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($file);
        }
        return strlen($file);
    }
}