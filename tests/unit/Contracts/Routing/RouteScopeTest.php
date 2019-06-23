<?php
/**
 *  * Created by mtils on 23.06.19 at 08:32.
 **/

namespace Ems\Contracts\Routing;


use Ems\TestCase;

class RouteScopeTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_interface()
    {
        $this->assertInstanceOf(RouteScope::class, $this->newScope());
    }

    /**
     * @test
     */
    public function get_and_set_id()
    {
        $this->assertEquals(85, $this->newScope()->setId(85)->getId());
    }

    /**
     * @test
     */
    public function get_and_set_name()
    {
        $this->assertEquals('Alfons', $this->newScope()->setName('Alfons')->getName());
        $this->assertEquals('Alfons', (string)$this->newScope()->setName('Alfons'));
    }

    /**
     * @test
     */
    public function manage_aliases()
    {
        $scope = $this->newScope(1, 'www');

        $this->assertSame([], $scope->aliases());
        $this->assertSame($scope, $scope->addAlias('www1'));
        $this->assertEquals(['www1'], $scope->aliases());

        $this->assertSame($scope, $scope->addAlias('www2'));
        $this->assertEquals(['www1', 'www2'], $scope->aliases());

        $this->assertSame($scope, $scope->addAlias('www3', 'www4'));
        $this->assertEquals(['www1', 'www2', 'www3', 'www4'], $scope->aliases());

        $this->assertSame($scope, $scope->removeAlias('www2'));
        $this->assertEquals(['www1', 'www3', 'www4'], $scope->aliases());

        $this->assertSame($scope, $scope->removeAlias('www1', 'www4'));
        $this->assertEquals(['www3'], $scope->aliases());

        $this->assertSame($scope, $scope->clearAliases());
        $this->assertSame([], $scope->aliases());
    }
    protected function newScope($id=null, $name='', array $aliases=[])
    {
        return new GenericRouteScope($id, $name, $aliases);
    }
}