<?php
/**
 *  * Created by mtils on 12.09.18 at 12:31.
 **/

namespace Ems\Core\Storages;


use Ems\Contracts\Core\IdGenerator;
use Ems\Contracts\Core\PushableStorage;
use Ems\Contracts\Core\Storage;
use Ems\Core\Url;
use Ems\IntegrationTest;
use Ems\Testing\FilesystemMethods;

class ArrayStorageTest extends IntegrationTest
{
    use FilesystemMethods;

    /**
     * @test
     */
    public function implements_interface()
    {
        $this->assertInstanceOf(Storage::class, $this->newStorage());
    }

    /**
     * @test
     */
    public function storageType_is_memory()
    {
        $this->assertEquals(Storage::MEMORY, $this->newStorage()->storageType());
    }

    /**
     * @test
     */
    public function persist_returns_true()
    {
        $this->assertTrue($this->newStorage()->persist());
    }

    /**
     * @test
     */
    public function isBuffered_returns_true()
    {
        $this->assertFalse($this->newStorage()->isBuffered());
    }

    /**
     * @test
     */
    public function purge_clears_storage()
    {
        $data = ['test' => 'one'];
        $storage = $this->newStorage($data);
        $this->assertEquals($data['test'],$storage['test']);
        $this->assertTrue($storage->purge());
        $this->assertFalse(isset($storage['test']));
    }

    /**
     * @param string $dir
     *
     * @return ArrayStorage
     */
    protected function newStorage($data=[])
    {
        return new ArrayStorage($data);
    }
}