<?php
/**
 *  * Created by mtils on 28.12.17 at 06:52.
 **/

namespace Ems\Model;

/**
 * Class Search
 *
 * This is the deprecated first version. The class looks like a default
 * implementation but it is basically the opposite.
 * A search is a by intention reduced search/list interface. A SearchEngine is
 * generally the opposite...
 *
 * @package Ems\Model
 * @deprecated Use EngineBasedSearch
 */
class Search extends EngineBasedSearch
{
    /**
     * Get result from SearchEngine.
     *
     * @return \Ems\Contracts\Model\Result
     */
    protected function getFromEngine()
    {
        $this->parseInputOnce();
        return $this->engine->search($this->buildConditions($this->filters), $this->sorting(), $this->queryKeys());
    }

    /**
     * Get result from Search Engine
     *
     * @param array    $filters
     * @param array    $sorting
     * @param string[] $queryKeys
     *
     * @return \Ems\Contracts\Model\Result
     */
    protected function getResultFromEngine($filters, $sorting, $queryKeys)
    {
        return $this->getFromEngine();
    }
}