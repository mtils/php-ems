<?php
/**
 *  * Created by mtils on 30.11.2021 at 20:51.
 **/

namespace Ems\View\Exceptions;

use Ems\Contracts\Core\Errors\NotFound;
use Ems\Contracts\View\Exceptions\ViewException;
use Throwable;

use function implode;

class ViewNotFoundException extends ViewException implements NotFound
{
    /**
     * @var string
     */
    protected $view = '';

    /**
     * @var array
     */
    protected $paths = [];

    public function __construct($view = "", array $paths=[], Throwable $previous = null)
    {
        $this->view = $view;
        $this->paths = $paths;
        parent::__construct($this->createMessage($view, $paths), 4040, $previous);
    }

    /**
     * @return string
     */
    public function getView() : string
    {
        return $this->view;
    }

    /**
     * @param string $view
     * @return ViewNotFoundException
     */
    public function setView($view) : ViewNotFoundException
    {
        $this->view = $view;
        return $this;
    }

    /**
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     * @return ViewNotFoundException
     */
    public function setPaths(array $paths): ViewNotFoundException
    {
        $this->paths = $paths;
        return $this;
    }


    protected function createMessage(string $view, array $paths)
    {
        return "$view not found in paths: " . implode(',',$paths);
    }
}