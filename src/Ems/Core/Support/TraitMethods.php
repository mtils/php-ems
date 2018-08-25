<?php
/**
 *  * Created by mtils on 25.08.18 at 06:50.
 **/

namespace Ems\Core\Support;


use Ems\Contracts\Core\Type;
use function call_user_func;
use function method_exists;

trait TraitMethods
{
    /**
     * @var array
     */
    protected $_allTraits;

    /**
     * Call a method an all traits. The trait class will somehow be added to
     * $method. (callAllTraits('boot') => bootMyTrait()).
     * If the concatenated method exists on one of the traits it will be called.
     *
     * @param string $method
     * @param array  $args (optional)
     *
     * @return void
     */
    protected function callOnAllTraits($method, array $args=[])
    {
        foreach ($this->collectAllTraits() as $trait) {
            $this->callTraitMethod($this->buildTraitMethod($method, $trait), $args);
        }
    }

    /**
     * Collect all traits that should be called.
     *
     * @return array
     */
    protected function collectAllTraits()
    {
        if ($this->_allTraits === null) {
            $this->_allTraits = Type::traits($this, true);
            unset($this->_allTraits[TraitMethods::class]); // exclude this trait
        }
        return $this->_allTraits;
    }


    /**
     * Build the trait method name to call.
     *
     * @param string $method
     * @param string $traitClass
     *
     * @return string
     */
    protected function buildTraitMethod($method, $traitClass)
    {
        return $method . Type::short($traitClass);
    }

    /**
     * Call a trait method (if it exists)
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    protected function callTraitMethod($method, array $args=[])
    {
        if (method_exists($this, $method)) {
            return call_user_func([$this, $method], ...$args);
        }
        return null;
    }

}