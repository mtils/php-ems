<?php
/**
 *  * Created by mtils on 14.09.18 at 14:48.
 **/

namespace Ems\Tree;


use function array_values;
use Ems\Contracts\Tree\CanHaveParent;
use Ems\Contracts\Tree\Children;
use Ems\Contracts\Tree\Node;
use Ems\Model\GenericResult;
use Ems\Model\OrmCollectionMethods;
use OutOfBoundsException;
use function spl_object_hash;

class GenericChildren extends GenericResult implements Children
{
    use OrmCollectionMethods;

    /**
     * @var Node[]
     */
    protected $data;

    /**
     * @inheritDoc
     */
    public function append(CanHaveParent $node)
    {
        // Add every node once
        if ($this->findNodeIndex($node) === null) {
            $this->data[] = $node;
            if ($this->_creator instanceof Node) {
                $node->setParent($this->_creator);
            }
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function remove(CanHaveParent $node)
    {
        $index = $this->findNodeIndex($node);
        if ($index === null) {
            throw new OutOfBoundsException("Node not found");
        }

        unset($this->data[$this->findNodeIndex($node)]);

        $this->data = array_values($this->data);

        $node->clearParent();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clear()
    {
        $this->data = [];
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * Try to find the node and returns its index
     *
     * @param Node $node
     *
     * @return int|null
     */
    protected function findNodeIndex(Node $node)
    {

        foreach ($this->data as $i=>$added) {
            if ($added->getId() == $node->getId()) {
                return $i;
            }
        }

        // Not found? try it by spl_object_hash
        $nodeHash = spl_object_hash($node);

        foreach ($this->data as $i=>$added) {
            if (spl_object_hash($added) == $nodeHash) {
                return $i;
            }
        }

        return null;

    }

}