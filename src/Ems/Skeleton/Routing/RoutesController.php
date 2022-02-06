<?php
/**
 *  * Created by mtils on 01.02.2022 at 18:36.
 **/

namespace Ems\Skeleton\Routing;

use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\ResponseFactory;
use Ems\Core\Response;
use Ems\Routing\RouteSearch;
use UnexpectedValueException;

use function array_keys;
use function implode;
use function iterator_to_array;
use function str_split;

class RoutesController
{
    protected $columnMap = [
        'v' => RouteSearch::METHODS,
        'p' => RouteSearch::PATTERN,
        'n' => RouteSearch::NAME,
        'c' => RouteSearch::CLIENTS,
        's' => RouteSearch::SCOPES,
        'm' => RouteSearch::MIDDLEWARE
    ];

    /**
     * @var string
     */
    protected $defaultColumns = 'vpncm';

    /**
     * Show a list of all routes (and commands)
     *
     * @param Input $input
     * @param RouteSearch $search
     * @param ResponseFactory $responseFactory
     *
     * @return Response
     */
    public function index(Input $input, RouteSearch $search, ResponseFactory $responseFactory) : Response
    {
        $search->apply(iterator_to_array($input))->setCreator($this);

        $keys = $this->getKeys(isset($input['columns']) && $input['columns'] ? $input['columns'] : $this->defaultColumns);
        $search->setKeys($keys);

        return $responseFactory->view('routes.index', [
            'routes'    => $search,
            'keys'      => $search->keys()
        ]);
    }

    /**
     * @param string $shortCuts
     * @return array
     */
    protected function getKeys(string $shortCuts) : array
    {
        $keys = [];
        foreach (str_split($shortCuts) as $shortcut) {
            if (!isset($this->columnMap[$shortcut])) {
                $known = implode(',', array_keys($this->columnMap));
                throw new UnexpectedValueException("Column shortcut $shortcut not known. I know only: $known.");
            }
            $keys[] = $this->columnMap[$shortcut];
        }
        return $keys;
    }

}