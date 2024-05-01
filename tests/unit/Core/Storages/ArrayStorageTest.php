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
use PHPUnit\Framework\Attributes\Test;

class ArrayStorageTest extends IntegrationTest
{
    use FilesystemMethods;

    #[Test] public function implements_interface()
    {
        $this->assertInstanceOf(Storage::class, $this->newStorage());
    }

    #[Test] public function storageType_is_memory()
    {
        $this->assertEquals(Storage::MEMORY, $this->newStorage()->storageType());
    }

    #[Test] public function persist_returns_true()
    {
        $this->assertTrue($this->newStorage()->persist());
    }

    #[Test] public function isBuffered_returns_true()
    {
        $this->assertFalse($this->newStorage()->isBuffered());
    }

    #[Test] public function purge_clears_storage()
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