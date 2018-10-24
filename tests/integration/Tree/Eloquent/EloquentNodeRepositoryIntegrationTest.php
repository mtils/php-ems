<?php
/**
 *  * Created by mtils on 14.09.18 at 15:25.
 **/

namespace Ems\Tree\Eloquent;


use Ems\Contracts\Core\Identifiable;
use Ems\Contracts\Tree\Children;
use Ems\Contracts\Tree\Node;
use Ems\Contracts\Tree\NodeRepository as NodeRepositoryContract;
use Ems\Model\Eloquent\IdentifiableByKeyTrait;
use Ems\TestCase;
use Ems\Testing\Eloquent\MigratedDatabase;
use Ems\Tree\GenericChildren;
use Illuminate\Database\Eloquent\Model;
use OutOfBoundsException;

class EloquentNodeRepositoryIntegrationTest extends TestCase
{
    use MigratedDatabase;

    /**
     * @test
     */
    public function implements_interface()
    {
        $this->assertInstanceOf(NodeRepositoryContract::class, $this->newRepository());
    }

    /**
     * @test
     */
    public function store_creates_rootNode()
    {
        $repo = $this->newRepository();
        $this->assertEquals('name', $repo->getSegmentKey());
        $node = $repo->store(['name' => 'root-1']);
        $this->assertInstanceOf(EloquentNode::class, $node);
        $this->assertTrue($node->isRoot());
        $this->assertGreaterThan(0, $node->getId());
    }

    /**
     * @test
     */
    public function get_created_node_by_getByPath()
    {
        $repo = $this->newRepository();
        $rootNode = $repo->getByPath('/root-1');
        $this->assertEquals('root-1', $rootNode->getAttribute('name'));

    }

    /**
     * @test
     */
    public function get_created_node_by_getByPathOrFail()
    {
        $repo = $this->newRepository();
        $rootNode = $repo->getByPathOrFail('/root-1');
        $this->assertEquals('root-1', $rootNode->getAttribute('name'));

    }

    /**
     * @test
     * @expectedException \Ems\Model\Eloquent\NotFoundException
     */
    public function getByPathOrFail_throws_exception_if_not_found()
    {
        $repo = $this->newRepository();
        $repo->getByPathOrFail('/root-2');
    }

    /**
     * @test
     */
    public function get_created_node_by_get()
    {
        $repo = $this->newRepository();
        /** @var EloquentNode $rootNode */
        $rootNode = $repo->getByPath('/root-1');
        /** @var EloquentNode $node */
        $node = $repo->get($rootNode->getId());
        $this->assertEquals($rootNode->toArray(), $node->toArray());
        $node2 = $repo->getOrFail($rootNode->getId());
        /** @var EloquentNode $node2 */
        $this->assertEquals($rootNode->toArray(), $node2->toArray());

    }

    /**
     * @test
     * @expectedException \Ems\Model\Eloquent\NotFoundException
     */
    public function getOrFail_throws_NotFoundException()
    {
        $repo = $this->newRepository();
        $repo->getOrFail(1234567890);
    }

    /**
     * @test
     */
    public function getByPath_returns_default_on_fail()
    {
        $repo = $this->newRepository();
        $default = $repo->make(['name'=>'default']);
        $rootNode = $repo->getByPath('/root-2', $default);
        $this->assertEquals('default', $rootNode->getAttribute('name'));

    }

    /**
     * @test
     */
    public function store_children_on_rootNode()
    {
        $repo = $this->newRepository();
        $rootNode = $repo->getByPath('/root-1');

        $children = $this->getChildrenData();

        foreach ($children as $childAttributes) {
            /** @var Model $child */
            $child = $repo->asChildOf($rootNode)->store($childAttributes);
            $this->assertEquals($childAttributes['name'], $child->getAttribute('name'));
            $this->assertEquals($childAttributes['title'], $child->getAttribute('title'));

            /** @var Node $child */
            $this->assertGreaterThan(1, $child->getId());
        }
    }

    /**
     * @test
     */
    public function children_returns_created_children()
    {
        $repo = $this->newRepository();
        $rootNode = $repo->getByPath('/root-1');
        foreach ($repo->children($rootNode) as $child) {
            $this->assertEquals($rootNode->getId(), $child->parent_id);
        }
    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function children_throws_exception_on_depth()
    {
        $repo = $this->newRepository();
        $rootNode = $repo->getByPath('/root-1');
        $repo->recursive(1)->children($rootNode);
    }

    /**
     * @test
     */
    public function get_with_depth_1()
    {
        $repo = $this->newRepository();
        $rootNode = $repo->getByPath('/root-1');

        $node = $repo->recursive(1)->get($rootNode->getId());

        $this->assertEquals($rootNode->getId(), $node->getId());

        $this->assertTrue($node->hasChildren());
        $children = $node->getChildren();
        $childArray = $this->getChildrenData();

        $this->assertInstanceOf(GenericChildren::class, $children);
        $this->assertCount(count($childArray), $children);

        foreach ($childArray as $childData) {
            $node = $this->findInChildren($childData['name'], $children);
            /** @var Model $node */
            $this->assertEquals($node->getAttribute('name'), $childData['name']);
            $this->assertEquals($node->getParent()->getId(), $rootNode->getId());
            $path = $rootNode->getPath() . '/' . $node->getPathSegment();
            $this->assertEquals($path, $node->getPath());
        }

    }

    /**
     * @test
     */
    public function get_with_depth_1_without_children()
    {
        $repo = $this->newRepository();

        $node = $repo->recursive(1)->get(2);
        $this->assertFalse($node->hasChildren());

        $children = $node->getChildren();

        $this->assertInstanceOf(GenericChildren::class, $children);
        $this->assertCount(0, $children);


    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function get_with_depth_2()
    {
        $repo = $this->newRepository();
        $rootNode = $repo->getByPath('/root-1');

        $repo->recursive(2)->get($rootNode->getId());

    }

    /**
     * @test
     */
    public function get_with_depth_1_for_non_existing_node()
    {
        $repo = $this->newRepository();

        $this->assertNull($repo->recursive(1)->get(123456789));


    }

    /**
     * @test
     */
    public function getByPath_with_depth_1()
    {
        $repo = $this->newRepository();

        $rootNode = $repo->recursive(1)->getByPath('/root-1');

        $this->assertEquals('/root-1', $rootNode->getPath());

        $children = $rootNode->getChildren();
        $childArray = $this->getChildrenData();

        $this->assertInstanceOf(GenericChildren::class, $children);
        $this->assertCount(count($childArray), $children);

        foreach ($childArray as $childData) {
            /** @var Model $node */
            $node = $this->findInChildren($childData['name'], $children);
            $this->assertEquals($node->getAttribute('name'), $childData['name']);
        }

    }

    /**
     * @test
     * @expectedException \Ems\Core\Exceptions\NotImplementedException
     */
    public function getByPath_with_depth_2()
    {
        $repo = $this->newRepository();
        $repo->recursive(2)->getByPath('/root-1');

    }

    /**
     * @test
     */
    public function parent_returns_parent()
    {
        $repo = $this->newRepository();


        $rootNode = $repo->getByPathOrFail('/root-1');
        $child = $repo->getByPathOrFail('/root-1/child-3');



        $this->assertEquals($rootNode->getId(), $repo->parent($child)->getId());

    }

    /**
     * @test
     */
    public function parent_returns_null_on_no_parent()
    {
        $repo = $this->newRepository();

        $rootNode = $repo->getByPath('/root-1');

        $this->assertNull($repo->parent($rootNode));

    }

    /**
     * @test
     */
    public function getPathKey_and_setPathKey()
    {
        $repo = $this->newRepository();
        $this->assertSame($repo, $repo->setPathKey('foo'));
        $this->assertEquals($repo->getPathKey(), 'foo');
    }

    /**
     * @test
     */
    public function getParentIdKey_and_setParentIdKey()
    {
        $repo = $this->newRepository();
        $this->assertSame($repo, $repo->setParentIdKey('foo_id'));
        $this->assertEquals($repo->getParentIdKey(), 'foo_id');
    }

    /**
     * @test
     * @expectedException \Ems\Contracts\Core\Exceptions\TypeException
     */
    public function passing_non_node_model_throws_exception()
    {
        $repo = $this->newRepository();
        $model = new EloquentNodeRepositoryIntegrationTest_Model();
        $repo->save($model);
    }

    /**
     * @test
     */
    public function EloquentNode_clearParent()
    {
        $repo = $this->newRepository();
        $child = $repo->getByPathOrFail('/root-1/child-2');
        $newParent = $repo->getByPathOrFail('/root-1/child-3');

        $this->assertNull($child->getParent());

        // It has a parent id, the parent is just not loaded
        $this->assertTrue($child->hasParent());

        $child->setParent($newParent);

        $this->assertSame($newParent, $child->getParent());
        $this->assertEquals($newParent->getId(), $child->getParentId());

        $this->assertSame($child, $child->clearParent());

        $this->assertNull($child->getParentId());
        $this->assertNull($child->getParent());
        $this->assertFalse($child->hasParent());


    }

    /**
     * @test
     */
    public function EloquentNode_removeChild()
    {
        $repo = $this->newRepository();
        $child = $repo->getByPath('/root-1/child-2');

        $rootNodeWithChildren = $repo->recursive(1)->getByPath('/root-1');

        $this->assertCount(count($this->getChildrenData()), $rootNodeWithChildren->getChildren());

        if (!$node = $this->findInChildren($child->name, $rootNodeWithChildren->getChildren(), 'name')) {
            $this->fail('The node was not found');
        }

        $rootNodeWithChildren->getChildren()->remove($child);

        $this->assertCount(count($this->getChildrenData())-1, $rootNodeWithChildren->getChildren());

        if ($node = $this->findInChildren($child->name, $rootNodeWithChildren->getChildren(), 'name')) {
            $this->fail('The node was not removed');
        }

        $this->assertFalse($child->hasParent());
        $this->assertNull($child->getParent());

    }

    /**
     * @test
     */
    public function EloquentNode_getLevel()
    {
        $repo = $this->newRepository();
        $child = $repo->getByPath('/root-1/child-2');
        $this->assertNull($child->getLevel());
        $child->level = 12;
        $this->assertEquals(12, $child->getLevel());

    }

    /**
     * @test
     */
    public function EloquentNode_getPathSegment()
    {
        $repo = $this->newRepository();
        $child = $repo->getByPath('/root-1/child-2');
        $this->assertEquals($child->getPathSegment(), $child->getAttribute('name'));

    }

    /**
     * @test
     */
    public function EloquentNode_getParentId()
    {
        $repo = $this->newRepository();
        $child = $repo->getByPath('/root-1/child-2');
        $parent = $repo->parent($child);
        $this->assertEquals($parent->getId(), $child->getParentId());

    }

    /**
     * @test
     * @expectedException OutOfBoundsException
     */
    public function EloquentNode_removeChild_throws_exception_if_child_not_found()
    {
        $repo = $this->newRepository();
        $newRoot = $repo->store(['name' => 'root-2']);


        $rootNodeWithChildren = $repo->recursive(1)->getByPath('/root-1');

        $rootNodeWithChildren->getChildren()->remove($newRoot);

    }

    /**
     * @test
     */
    public function EloquentNode_getPath()
    {
        $repo = $this->newRepository();

        $rootNode = $repo->recursive(1)->getByPath('/root-1');

        $this->assertEquals('/root-1', $rootNode->getPath());

        $children = $rootNode->getChildren();
        $childArray = $this->getChildrenData();

        $this->assertInstanceOf(GenericChildren::class, $children);
        $this->assertCount(count($childArray), $children);

        foreach ($childArray as $childData) {
            /** @var Model $node */
            $node = $this->findInChildren($childData['name'], $children);
            $this->assertEquals($node->getAttribute('name'), $childData['name']);
            foreach ([1,2,3,4] as $level2Id) {

                $node->getChildren()->append($repo->make([
                    'title'    => "Child #" . $node->getId() . "-$level2Id",
                    'name' => $node->getId() . "-$level2Id"
                ]));
            }
        }

    }

    /**
     * @test
     */
    public function store_children_on_level3()
    {
        $repo = $this->newRepository();

        $parentNode = $repo->recursive(1)->getByPath('/root-1/child-2');

        $this->assertEquals('/root-1/child-2', $parentNode->getPath());

        $children = $this->getChildrenData();

        foreach ($children as $childAttributes) {

            /** @var EloquentNode $child */
            $child = $repo->asChildOf($parentNode)->store($childAttributes);
            $this->assertEquals($childAttributes['name'], $child->getAttribute('name'));
            $this->assertEquals($childAttributes['title'], $child->getAttribute('title'));

            $path = $parentNode->getPath() . '/' . $childAttributes['name'];
            $this->assertEquals($path, $child->getPath());
            $this->assertSame($parentNode, $child->getParent());
            $this->assertEquals($parentNode->getId(), $child->getParentId());
        }

        //$this->dumpTable('nodes');

    }

    /**
     * @test
     */
    public function ancestors_on_level3()
    {
        $repo = $this->newRepository();

        $childNode = $repo->getByPath('/root-1/child-2/child-3');

        $ancestors = $repo->ancestors($childNode);

        $this->assertCount(2, $ancestors);

        $this->assertSame($childNode->getParent(), $ancestors[0]);
        $this->assertSame($childNode->getParent()->getParent(), $ancestors[1]);

        $this->assertEquals($ancestors[0]->getPath(), '/root-1/child-2');
        $this->assertEquals($ancestors[1]->getPath(), '/root-1');

    }

    /**
     * @test
     */
    public function ancestors_on_level2()
    {
        $repo = $this->newRepository();

        $childNode = $repo->getByPath('/root-1/child-2');

        $ancestors = $repo->ancestors($childNode);

        $this->assertCount(1, $ancestors);

        $this->assertSame($childNode->getParent(), $ancestors[0]);

        $this->assertEquals($ancestors[0]->getPath(), '/root-1');

    }

    /**
     * @test
     */
    public function ancestors_on_rootNode()
    {
        $repo = $this->newRepository();

        $childNode = $repo->getByPath('/root-1');

        $ancestors = $repo->ancestors($childNode);

        $this->assertCount(0, $ancestors);

        $this->assertSame([], $ancestors);

    }

    /**
     * @test
     */
    public function ancestors_on_manually_created_structure()
    {
        $repo = $this->newRepository();

        $rootNode = $repo->make(['name' => 'root-3']);

        $childNode = $repo->make(['name' => 'child-1']);

        $childNode2 = $repo->make(['name' => 'child-1']);

        $childNode2->setParent($childNode);

        $childNode->setParent($rootNode);

        $ancestors = $repo->ancestors($childNode2);

        $this->assertCount(2, $ancestors);

        $this->assertSame($childNode2->getParent(), $ancestors[0]);
        $this->assertSame($childNode2->getParent()->getParent(), $ancestors[1]);

        $this->assertEquals($ancestors[0]->getPath(), '/root-3/child-1');
        $this->assertEquals($ancestors[1]->getPath(), '/root-3');


    }

    /**
     * @test
     */
    public function findBySegment_finds_all_child_1_nodes()
    {
        $repo = $this->newRepository();

        $nodes = $repo->findBySegment('child-1');

        $segments = [];

        foreach ($nodes as $node) {
            $segments[] = $node->getPathSegment();
        }

        $this->assertEquals(['child-1', 'child-1'], $segments);

    }

    /**
     * @param Model|null $model
     * @return NodeRepository
     */
    protected function newRepository(Model $model=null)
    {
        $repo = new NodeRepository($model ?: new EloquentNodeRepositoryIntegrationTest_Node());
        $repo->setSegmentKey('name');
        return $repo;
    }

    /**
     * @param $needle
     * @param Children $children
     * @param string $property
     *
     * @return Node|null
     */
    protected function findInChildren($needle, Children $children, $property='name')
    {
        foreach ($children as $child) {
            if ($child->$property == $needle) {
                return $child;
            }
        }
        return null;
    }

    protected function getChildrenData()
    {
        return [
            [
                'title' => 'Child #1',
                'name'  => 'child-1'
            ],
            [
                'title'   => 'Child #2',
                'name'    => 'child-2'
            ],
            [
                'title' => 'Child #3',
                'name'  => 'child-3'
            ],
            [
                'title' => 'Child #4',
                'name'  => 'child-4'
            ]
        ];
    }
}

class EloquentNodeRepositoryIntegrationTest_Node extends EloquentNode
{
    protected $guarded = ['id', 'parent_id'];
    protected $table = 'nodes';

    /**
     * @inheritDoc
     */
    public function usesTimestamps()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getPathSegment()
    {
        return $this->getAttribute('name');
    }

    public function getPath()
    {
        if ($path = $this->getAttributeFromArray('path')) {
            return $path;
        }
        return parent::getPath();
    }

}

class EloquentNodeRepositoryIntegrationTest_Model extends Model implements Identifiable
{
    use IdentifiableByKeyTrait;


}