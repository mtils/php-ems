<?php
/**
 *  * Created by mtils on 17.08.19 at 14:48.
 **/

namespace Ems\Skeleton;


use Ems\Contracts\Routing\Input;
use Ems\Contracts\Routing\InputHandler;
use Ems\Core\Response;
use function call_user_func;

/**
 * Class ProxyInputHandler
 *
 * This class is to create an input handler on the fly out of your callable.
 * @package Ems\Core
 */
class ProxyInputHandler implements InputHandler
{
    /**
     * @var callable
     */
    protected $source;

    public function __construct(callable $source)
    {
        $this->source = $source;
    }

    /**
     * {@inheritDoc}
     *
     * @param Input $input
     *
     * @return Response
     */
    public function __invoke(Input $input)
    {
        return call_user_func($this->source, $input);
    }

}