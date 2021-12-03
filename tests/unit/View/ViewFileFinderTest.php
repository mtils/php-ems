<?php
/**
 *  * Created by mtils on 29.11.2021 at 20:53.
 **/

namespace Ems\View;

use Ems\TestCase;
use Ems\TestData;

/**
 *
 */
class ViewFileFinderTest extends TestCase
{
    use TestData;

    /**
     * @test
     */
    public function it_instantiates()
    {
        $this->assertInstanceOf(ViewFileFinder::class, $this->make());
    }

    /**
     * @test
     */
    public function it_finds_view()
    {
        $finder = $this->make();
        $testDir = $this->dirOfTests('views');
        $finder->addPath($testDir);
        $this->assertEquals("$testDir/test-view.php", $finder->file('test-view'));
        $this->assertEquals("$testDir/users/index.php", $finder->file('users.index'));
    }

    protected function make()
    {
        return new ViewFileFinder();
    }
}