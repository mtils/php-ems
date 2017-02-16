<?php


namespace Ems\XType;


use Ems\Contracts\XType\TypeFactory as TypeFactoryContract;
use Ems\Core\Patterns\TraitOfResponsibility;


class TypeFactoryChain implements TypeFactoryContract
{
    use TraitOfResponsibility;

    /**
     * {@inheritdoc}
     *
     * @param mixed $config
     *
     * @return bool
     **/
    public function canCreate($config)
    {
        return (bool)$this->findReturningTrue('canCreate', $config);
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $config
     *
     * @return \Ems\Contracts\XType\XType
     **/
    public function toType($config)
    {
        return $this->findReturningTrueOrFail('canCreate', $config)
                    ->toType($config);
    }

}
