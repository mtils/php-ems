<?php

namespace Ems\Assets;

use Ems\Contracts\Assets\Builder as BuilderContract;
use Ems\Contracts\Assets\BuildConfig as BuildConfigContract;
use Ems\Contracts\Assets\Compiler as CompilerContract;
use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Contracts\Core\Filesystem;

class Builder implements BuilderContract
{
    /**
     * @var \Ems\Contracts\Assets\Registry
     **/
    protected $registry;

    /**
     * @var \Ems\Contracts\Assets\Compiler
     **/
    protected $compiler;

    /**
     * @var \Ems\Contracts\Core\Filesystem
     **/
    protected $files;

    /**
     * @var callable
     **/
    protected $buildListener;

    /**
     * @param \Ems\Contracts\Assets\Registry $registry
     * @param \Ems\Contracts\Assets\Compiler $compiler
     * @param \Ems\Contracts\Core\Filesystem $files
     **/
    public function __construct(RegistryContract $registry,
                                CompilerContract $compiler,
                                Filesystem $files)
    {
        $this->registry = $registry;
        $this->compiler = $compiler;
        $this->files = $files;
        $this->buildListener = function (BuildConfigContract $config, $path) {};
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ems\Contracts\Assets\BuildConfig $config
     *
     * @return string the written path
     **/
    public function build(BuildConfigContract $config)
    {
        $absolutePath = $this->absolutePath($config);

        $parserOptions = array_merge($config->parserOptions(), [
            '*' => [
                'build_config' => $config,
                'target_path'  => $absolutePath,
            ],
        ]);

        $compiled = $this->compiler->compile($config->collection(), $config->parserNames(), $parserOptions);

        $absolutePath = $this->absolutePath($config);

        $this->files->write($absolutePath, $compiled);

        call_user_func($this->buildListener, $config, $absolutePath);

        return $absolutePath;
    }

    /**
     * {@inheritdoc}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function whenBuilt(callable $listener)
    {
        $this->buildListener = $listener;

        return $this;
    }

    protected function absolutePath($config)
    {
        return $this->registry->to($config->group())->absolute($config->target());
    }
}
