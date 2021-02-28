<?php
/**
 *  * Created by mtils on 2/28/21 at 9:22 AM.
 **/

namespace unit\Config;


use ArrayAccess;
use Ems\Config\Config;
use Ems\TestCase;
use Traversable;

class ConfigTest extends TestCase
{
    /**
     * @test
     */
    public function it_instantiates()
    {
        $config = $this->make();
        $this->assertInstanceOf(Config::class, $config);
        $this->assertInstanceOf(ArrayAccess::class, $config);
        $this->assertInstanceOf(Traversable::class, $config);
    }

    protected function make()
    {
        return new Config();
    }
}