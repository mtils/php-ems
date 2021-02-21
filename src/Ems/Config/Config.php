<?php
/**
 *  * Created by mtils on 2/20/21 at 9:32 AM.
 **/

namespace Ems\Config;


use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Traversable;
use UnderflowException;
use UnexpectedValueException;

use function is_array;

class Config implements ArrayAccess, IteratorAggregate
{
    /**
     * @var array|null
     */
    protected $compiled;

    /**
     * @var array[]|Traversable[]
     */
    protected $sources = [];

    /**
     * @var callable[]
     */
    protected $postProcessors = [];

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->compileIfNeeded();
        return isset($this->compiled[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        $this->compileIfNeeded();
        return isset($this->compiled[$offset]) ? $this->compiled[$offset] : null;
    }

    /**
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->compileIfNeeded();
        $this->compiled[$offset] = $value;
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->compiled[$offset]);
        }
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        $this->compileIfNeeded();
        return new ArrayIterator($this->compiled);
    }

    /**
     * Append a source to the sources. Previously set sources will be overwritten
     * by following. So appended will overwrite previously set sources.
     *
     * @param array|Traversable $source
     * @param string|null $name
     */
    public function appendSource($source, $name=null)
    {
        $this->sources[$name ?: $this->makeSourceName()] = $this->checkAndReturnSource($source);
    }

    /**
     * Prepend a source to the sources. Previously set sources will be overwritten
     * by following. So prepended will be overwritten by later set sources.
     *
     * @param array|Traversable $source
     * @param string|null $name
     */
    public function prependSource($source, $name=null)
    {
        $copy = $this->sources;

        $this->sources = [
            $name ?: $this->makeSourceName() => $this->checkAndReturnSource($source)
        ];

        foreach ($copy as $name=>$source) {
            $this->sources[$name] = $source;
        }
    }

    /**
     * Clear the compiled result
     */
    public function clearCompiled()
    {
        $this->compiled = null;
    }

    /**
     * Add a processing callable that works over the built config to do some
     * string replacements or other stuff.
     * The current processed version is passed to it and the second argument is
     * the unprocessed first version.
     *
     * @param callable $processor
     */
    public function appendPostProcessor(callable $processor)
    {
        $this->postProcessors[] = $processor;
    }

    /**
     * Prepend a processing callable.
     * @see self::appendPostProcessor()
     *
     * @param callable $processor
     */
    public function prependPostProcessor(callable $processor)
    {
        $copy = $this->postProcessors;
        $this->postProcessors = [$processor];
        foreach ($copy as $processor) {
            $this->postProcessors[] = $processor;
        }
    }

    /**
     * Compile the config if no compiled config was compiled already.
     */
    protected function compileIfNeeded()
    {
        if ($this->compiled !== null) {
            return;
        }
        if (!$this->sources) {
            throw new UnderflowException('No sources were added to the config');
        }
        $this->compiled = $this->compile($this->sources, $this->postProcessors);
    }

    /**
     * Merge all sources and post process the result.
     *
     * @param array      $sources
     * @param callable[] $processors
     *
     * @return array
     */
    protected function compile(array $sources, array $processors) : array
    {
        $config = [];
        foreach ($sources as $name=>$source) {
            foreach ($source as $key=>$value) {
                $config[$key] = $value;
            }
        }

        if (!$processors) {
            return $config;
        }

        $processed = $config;
        foreach ($processors as $processor) {
            $processed = $processor($processed, $config);
        }

        return $processed;

    }

    /**
     * @return string
     */
    protected function makeSourceName(): string
    {
        for ($i=0; $i<100; $i++) {
            $name = "source-$i";
            if (!isset($this->sources[$name])) {
                return $name;
            }
        }
        return '';
    }

    /**
     * @param mixed $source
     *
     * @return Traversable|array
     *
     * @throws UnexpectedValueException
     */
    protected function checkAndReturnSource($source)
    {
        if (!is_array($source) && !$source instanceof Traversable) {
            throw new UnexpectedValueException('Source has to be array or Traversable');
        }
        return $source;
    }
}