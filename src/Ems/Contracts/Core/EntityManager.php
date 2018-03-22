<?php
/**
 *  * Created by mtils on 06.02.18 at 18:12.
 **/

namespace Ems\Contracts\Core;

/**
 * Interface EntityManager
 *
 * The EntityManager is a central place to register providers to provide
 * entities.
 * For performance reasons you should add classes when bootstrapping it.
 * The classes then get resolved.
 *
 * You can also add an callable extension via the Extendable interface.
 * The entity will then be resolved by your callable if no provider
 * for the specific class exists.
 *
 * @package Ems\Contracts\Core
 */
interface EntityManager extends Extendable
{

    /**
     * Retrieve an identifiable object from any provider.
     * Pass an EntityPointer object or a class and an id to retrieve
     * the object.
     *
     * @param string|EntityPointer $classOrPointer
     * @param string|int           $id (optional)
     *
     * @return Identifiable|null
     */
    public function get($classOrPointer, $id=null);

    /**
     * Retrieve an identifiable object via self.:get() or throw an
     * exception.
     *
     * @param string|EntityPointer $classOrPointer
     * @param string|int           $id (optional)
     *
     * @return Identifiable
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     */
    public function getOrFail($classOrPointer, $id=null);

    /**
     * Retrieve a provider for an entity class. Or throw an
     * exception. The class is searched by string equality not
     * inheritance.
     *
     * @param string $class
     *
     * @return Provider
     *
     * @throws \Ems\Contracts\Core\Errors\NotFound
     */
    public function provider($class);

    /**
     * Add a provider class. You can add the class, the provider itself
     * or a callable to create the provider.
     * Don't confuse the callable with the extensions. The callable will
     * just create the provider instance, not any entities (like the callable
     * extensions).
     *
     * @param string                   $class
     * @param string|Provider|callable $provider
     *
     * @return self
     */
    public function setProvider($class, $provider);

    /**
     * Create an EntityPointer out of an entity if (and only if) the
     * manager could retrieve the object back.
     *
     * @param Identifiable $object
     *
     * @return EntityPointer
     */
    public function pointer(Identifiable $object);
}