<?php
/**
 *  * Created by mtils on 26.10.19 at 07:57.
 **/

namespace Ems\Testing;

use OutOfBoundsException;
use function array_map;
use function floor;
use function log;
use function memory_get_peak_usage;
use function memory_get_usage;
use function microtime;
use function pow;
use function round;
use function usort;

class Benchmark
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $marks = [];

    /**
     * @var array
     */
    protected $started = [];

    /**
     * @var bool
     */
    protected $trackMemory = false;

    /**
     * @var static[]
     */
    protected static $instances = [];

    /**
     * @var float
     */
    protected static $initialTime;

    /**
     * Benchmark constructor.
     *
     * @param $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->storeInitialMark();
    }

    /**
     * Store micro time (and memory if configured) for $name.
     *
     * @param string $name
     * @param string $group    (default:"main")
     * @param string $instance (default:"default")
     */
    public static function mark(string $name, string $group='main', string $instance='default')
    {
        static::instance($instance)->addMark($name, $group);
    }

    /**
     * @see static::doBegin()
     *
     * @param string $name
     * @param string $group    (default: "main")
     * @param string $instance (default:"default")
     */
    public static function begin($name, $group='main', $instance='default')
    {
        static::instance($instance)->doBegin($name, $group);
    }

    /**
     * @see static::doEnd()
     *
     * @param string $name
     * @param string $group    (default: "main")
     * @param string $instance (default:"default")
     */
    public static function end($name, $group='main', $instance='default')
    {
        static::instance($instance)->doEnd($name, $group);
    }

    /**
     * @see static::storeMark()
     *
     * @param array  $mark
     * @param string $instance (default: "default")
     *
     */
    public static function raw(array $mark, string $instance='default')
    {
        static::instance($instance)->storeMark(
            $mark['name'],
            $mark['group'] ?? 'main',
            $mark['time'],
            $mark['memory'] ?? 0,
            $mark['duration'] ?? 0.0
        );
    }

    /**
     * Get the formatted peak memory usage.
     *
     * @return string
     */
    public static function peakMemory()
    {
        return static::memoryFormat(memory_get_peak_usage(true));
    }

    /**
     * Return the total duration from initial mark until this call.
     *
     * @return float
     */
    public static function totalDuration()
    {
        return microtime(true) - static::initialTime();
    }

    /**
     * Format a bytes value in the next corresponding unit (KB/MB/GB/...)
     *
     * @param int $bytes
     *
     * @return string
     */
    public static function memoryFormat($bytes)
    {
        $unit=['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        return @round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$unit[(int)$i];
    }

    /**
     * @param float $seconds
     *
     * @return float
     */
    public static function milliSeconds($seconds)
    {
        return round($seconds * 1000, 2);
    }

    /**
     * Store micro time (and memory if configured) for $name. Manually pass the
     * duration if you want to. Otherwise the duration will be calculated by
     * the difference of last added marks micro time.
     *
     * @param string $name
     * @param string $group     (default:main)
     * @param float $duration (optional)
     */
    public function addMark(string $name, string $group='main', float $duration=0.0)
    {
        $this->storeMark(
            $name,
            $group,
            microtime(true),
            $this->trackMemory ? memory_get_usage(true) : 0,
            $duration
        );
    }

    /**
     * Mark the start of $name. The difference to mark is that mark will calculate
     * the duration automatically by the last added mark.
     * If you really want to measure something like a database query you should
     * mark its start and its end.
     *
     * @param string $name
     * @param string $group
     */
    public function doBegin(string $name, string $group='main')
    {
        $this->started["$group.$name"] = [
            'name' => $name,
            'group' => $group,
            'time'  => microtime(true),
            'duration'  => 0.0
        ];
    }

    /**
     * Mark the end of a step you started to measure by doBegin().
     *
     * @param string $name
     * @param string $group
     */
    public function doEnd(string $name, string $group='main')
    {
        $key = "$group.$name";
        if (!isset($this->started[$key])) {
            throw new OutOfBoundsException("No started '$name' in group $group found");
        }

        $now = microtime(true);

        $this->storeMark(
            $name,
            $group,
            $now,
            $this->trackMemory ? memory_get_usage(true) : 0,
            $now - $this->started[$key]['time']
        );
        unset($this->started[$key]);
    }

    /**
     * Manually store a mark. You have to pass all data manually.
     *
     * @param string $name
     * @param string $group
     * @param float  $microTime
     * @param int    $memory
     * @param float  $duration
     */
    public function storeMark(string $name, string $group, float $microTime, int $memory, float $duration)
    {
        if (!isset($this->marks[$group])) {
            $this->marks[$group] = [];
        }
        $this->marks[$group][] = [
            'name'      => $name,
            'group'     => $group,
            'time'      => $microTime,
            'memory'    => $memory,
            'duration'  => $duration
        ];
    }

    /**
     * @param string $group (optional)
     *
     * @return array
     */
    public function getMarks(string $group='main'): array
    {
        if ($group) {
            $sorted = static::sortByTime($this->marks[$group]);
            return static::addMissingDurations($sorted);
        }
        $allMarks = [];
        array_map(function ($marks) use (&$allMarks) {
            $allMarks = array_merge($allMarks, $marks);
        },$this->marks);

        $sorted = static::sortByTime($allMarks);
        return static::addMissingDurations($sorted);
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return array_keys($this->marks);
    }

    /**
     * Return a (named) instance if you need multiple.
     *
     * @param string $name (default: "default")
     *
     * @return static
     */
    public static function instance(string $name='default'): Benchmark
    {
        if (!isset(static::$instances[$name])) {
            static::$instances[$name] = new static($name);
        }
        return static::$instances[$name];
    }

    /**
     * Return all creates instances
     * @return static[]
     */
    public static function instances(): array
    {
        return array_values(static::$instances);
    }

    /**
     * @return float
     */
    public static function initialTime(): float
    {
        if (is_float(static::$initialTime)) {
            return static::$initialTime;
        }
        static::$initialTime = $_SERVER['REQUEST_TIME_FLOAT'] ?? microtime(
                true
            );
        return static::$initialTime;
    }

    /**
     * Stores an initial mark for the start or application start.
     */
    protected function storeInitialMark()
    {
        $this->storeMark(
            'Init',
            'main',
            static::initialTime(),
            $this->trackMemory ? memory_get_usage(true) : 0,
            0
        );
    }

    /**
     * @param array $marks
     *
     * @return array
     */
    protected static function sortByTime(array $marks)
    {
        usort($marks, function ($a, $b) {
            if($a['time'] === $b['time']) {
                return 0;
            }
            return $a['time'] < $b['time'] ? -1 : 1;
        });
        return $marks;
    }

    /**
     * Calculate the duration of all marks that have non manually set duration.
     *
     * @param array $marks
     *
     * @return array
     */
    protected static function addMissingDurations(array $marks)
    {
        $lastTime = 0.0;
        $initialTime = static::initialTime();

        foreach ($marks as $key=>$mark) {

            $marks[$key]['absolute'] = $marks[$key]['time'] - $initialTime;

            if ($marks[$key]['duration'] !== 0.0) {
                $lastTime = $marks[$key]['time'];
                continue;
            }
            if ($lastTime) {
                $marks[$key]['duration'] = $marks[$key]['time'] - $lastTime;
            }
            $lastTime = $marks[$key]['time'];
        }
        return $marks;
    }
}