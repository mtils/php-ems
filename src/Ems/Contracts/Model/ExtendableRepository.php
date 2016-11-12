<?php

namespace Ems\Contracts\Model;

/**
 * An extendable repository allows to hooks in every crud
 * action. Assign a callable.
 **/
interface ExtendableRepository extends Repository
{
    /**
     * This is called before getting a record.
     *
     * signature should be function($query){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function getting(callable $listener);

    /**
     * This is called after getting a record.
     *
     * signature should be function($foundModel){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function got(callable $listener);

    /**
     * This is called after making a record.
     *
     * signature should be function($instaniatedModel){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function made(callable $listener);

    /**
     * This is called before storing a record.
     *
     * signature should be function($filledModel, $attributes){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function storing(callable $listener);

    /**
     * This is called after storing a record.
     *
     * signature should be function($storedModel, $attributes){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function stored(callable $listener);

    /**
     * This is called before filling a record with new attributes.
     *
     * signature should be function($unfilledModel, $allPassedAttributes){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function filling(callable $listener);

    /**
     * This is called after filling a record with new attributes.
     *
     * signature should be function($filledModel, $allPassedAttributes){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function filled(callable $listener);

    /**
     * This is called after updating a record.
     *
     * signature should be function($savedModel, $allPassedAttributes){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function updated(callable $listener);

    /**
     * This is called before saving a record.
     *
     * signature should be function($unsavedModel){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function saving(callable $listener);

    /**
     * This is called after saving a record.
     *
     * signature should be function($savedModel){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function saved(callable $listener);

    /**
     * This is called before deleting a record.
     *
     * signature should be function($undeletedModel){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function deleting(callable $listener);

    /**
     * This is called after deleting a record.
     *
     * signature should be function($deletedModel){}
     *
     * @param callable $listener
     *
     * @return self
     **/
    public function deleted(callable $listener);
}
