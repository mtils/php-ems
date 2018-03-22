<?php
/**
 *  * Created by mtils on 06.02.18 at 17:50.
 **/

namespace Ems\Queue;


use Ems\Contracts\Core\EntityManager;
use Ems\Contracts\Core\EntityPointer;
use Ems\Contracts\Core\Extendable;
use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Core\Serializer;
use Ems\Contracts\Core\Type;
use Ems\Core\Exceptions\UnsupportedParameterException;
use Ems\Core\Patterns\ExtendableByClassHierarchyTrait;
use Serializable;
use function is_object;
use function is_string;
use function unserialize;

/**
 * Class ArgumentSerializer
 *
 * When putting a call into the queue, every argument in that call
 * has to be serialized. This class performs exactly this operation.
 *
 * It only serializes objects and arrays. So if the result of unserialize is not
 * an array or object it will just return the serialized (mostly never really
 * serialized) value.
 *
 *
 * @package Ems\Queue
 */
class ArgumentSerializer implements Serializer, Extendable
{

    use ExtendableByClassHierarchyTrait;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * ArgumentSerializer constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     **/
    public function mimeType()
    {
        return 'application/vnd.php.serialized';
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed $value
     * @param array $options (optional)
     *
     * @return string
     **/
    public function serialize($value, array $options = [])
    {

        if (!is_object($value) && !is_array($value)) {
            return $value;
        }

        if (is_array($value) || $value instanceof Serializable) {
            return serialize($value);
        }

        if (!$value instanceof Identifiable) {
            throw new UnsupportedParameterException("Cannot serialize value of type " . Type::of($value));
        }

        if($pointer = $this->entityManager->pointer($value)) {
            return $this->serialize($pointer);
        };

        throw new UnsupportedParameterException("Cannot serialize value of type " . Type::of($value));

    }

    /**
     * {@inheritdoc}
     *
     * @param string $string
     * @param array $options (optional)
     *
     * @return mixed
     **/
    public function deserialize($string, array $options = [])
    {
        $plain = @unserialize($string);

        if ((!is_object($plain) && !is_array($plain)) || $plain === false) {
            return $string;
        }

        if ($plain instanceof EntityPointer) {
            return $this->entityManager->get($plain);
        }

        return $plain;

    }

    /**
     * Encode the arguments in a way the can be stored via json_encode or
     * serialize.
     *
     * @param array $args
     *
     * @return array
     */
    public function encode(array $args)
    {
        if (!$args) {
            return $args;
        }

        $serializable = [];

        foreach ($args as $arg) {
            $serializable[] = $this->serialize($arg);
        }

        return $serializable;
    }

    /**
     * Decode the values of the passed array
     *
     * @param array $args
     *
     * @return array
     */
    public function decode(array $args)
    {
        $decoded = [];

        foreach ($args as $arg) {

            if (!is_string($arg)) {
                $decoded[] = $arg;
                continue;
            }

            $decoded[] = $this->deserialize($arg);

        }

        return $decoded;
    }

}