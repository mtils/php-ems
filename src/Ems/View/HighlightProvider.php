<?php


namespace Ems\View;

use Ems\Contracts\View\Highlight as HighlightContract;
use Ems\Contracts\View\HighlightProvider as ProviderContract;
use Ems\Contracts\View\HighlightItemProvider as itemProvider;
use Ems\Contracts\Core\Renderer;


class HighlightProvider implements ProviderContract
{

    /**
     * @var \Ems\Contracts\Core\Renderer
     **/
    protected $renderer;

    /**
     * @var \Ems\Contracts\View\HighlightItemProvider
     **/
    protected $itemProvider;

    /**
     * @var callable
     **/
    protected $highlightFactory;

    /**
     * @param \Ems\Contracts\View\HighlightItemProvider $itemProvider
     * @param \Ems\Contracts\Core\Renderer
     **/
    public function __construct(ItemProvider $itemProvider, Renderer $renderer)
    {
        $this->renderer = $renderer;
        $this->itemProvider = $itemProvider;
    }

    /**
     * {@inheritdoc}
     *
     * @param int $limit (optional)
     * @return \Ems\Contracts\View\Highlight
     **/
    public function latest($limit=null)
    {
        return $this->createHighlight('latest', $limit);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $limit (optional)
     * @return \Ems\Contracts\View\Highlight
     **/
    public function top($limit=null)
    {
        return $this->createHighlight('top', $limit);
    }

    /**
     * {@inheritdoc}
     *
     * @param int $limit (optional)
     * @return \Ems\Contracts\View\Highlight
     **/
    public function some($limit=null)
    {
        return $this->createHighlight('some', $limit);
    }

    /**
     * Assign a custom callable to create the highlight objects
     *
     * @param callable $factory
     * @return self
     **/
    public function createHighlightBy(callable $factory)
    {
        $this->highlightFactory = $factory;
        return $this;
    }

    protected function createHighlight($method, $limit)
    {
        return $this->newHighlight()
                    ->setItemProvider($this->itemProvider)
                    ->setRenderer($this->renderer)
                    ->method($method)
                    ->limit($limit);

    }

    protected function newHighlight()
    {
        if ($this->highlightFactory) {
            return call_user_func($this->highlightFactory, HighlightContract::class);
        }

        return new Highlight;
    }


}
