<?php
/**
 *  * Created by mtils on 23.06.19 at 07:53.
 **/

namespace Ems\Contracts\Routing;


use function array_filter;
use function array_values;
use Ems\Contracts\Core\StringableTrait;
use function func_get_args;
use function in_array;
use function is_array;

class GenericRouteScope implements RouteScope
{
    use StringableTrait;

    /**
     * @var string|int
     */
    protected $id;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var array
     */
    protected $aliases = [];

    public function __construct($id=null, $name='', $aliases=[])
    {
        $this->setId($id);
        $this->setName($name);
        $this->aliases = $aliases;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed (int|string)
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string|int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Return a name for this object.
     *
     * @return string
     **/
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return string[]
     */
    public function aliases()
    {
        return $this->aliases;
    }

    /**
     * @param string|string[] $alias
     *
     * @return $this
     */
    public function addAlias($alias)
    {
        $aliases = is_array($alias) ? $alias : func_get_args();
        foreach ($aliases as $alias) {
            $this->aliases[] = $alias;
        }
        return $this;
    }

    /**
     * @param string|string[] $alias
     *
     * @return $this
     */
    public function removeAlias($alias)
    {
        $aliases = is_array($alias) ? $alias : func_get_args();
        $this->aliases = array_filter($this->aliases, function ($alias) use ($aliases) {
            return !in_array($alias, $aliases);
        });
        $this->aliases = array_values($this->aliases);
        return $this;
    }

    /**
     * @return $this
     */
    public function clearAliases()
    {
        $this->aliases = [];
        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @return string
     **/
    public function toString()
    {
        return $this->getName();
    }

}