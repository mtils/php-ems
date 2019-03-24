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

class PushableProxyStorageTest extends IntegrationTest
{
    use FilesystemMethods;

    /**
     * @test
     */
    public function implements_interface()
    {
        $this->assertInstanceOf(PushableStorage::class, $this->newStorage());
    }

    /**
     * @test
     */
    public function offsetPush_creates_offset()
    {
        $storage = $this->newStorage();

        $numbers = [
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',

        ];

        foreach ($numbers as $int=>$number) {
            $this->assertEquals($int, $storage->offsetPush($number));
        }

        foreach ($numbers as $int=>$number) {
            $this->assertEquals($number, $storage[$int]);
        }

        $baseStorage = $storage->getBaseStorage();

        foreach ($numbers as $int=>$number) {
            $this->assertEquals($number, $baseStorage[$int]);
        }

    }

    /**
     * @test
     */
    public function offsetPush_creates_offset_in_already_filled_array()
    {

        $numbers = [
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
        ];

        $baseStorage = $this->newBaseStorage($numbers);
        $storage = $this->newStorage($baseStorage);

        $this->assertEquals(10, $storage->offsetPush('ten'));
        $this->assertEquals(11, $storage->offsetPush('eleven'));

        foreach ($numbers as $int=>$number) {
            $this->assertEquals($number, $storage[$int]);
        }

        $baseStorage = $storage->getBaseStorage();

        foreach ($numbers as $int=>$number) {
            $this->assertEquals($number, $baseStorage[$int]);
        }

    }

    protected function newStorage(Storage $storage=null, IdGenerator $idGenerator=null)
    {
        $storage = $storage ?: $this->newBaseStorage();
        return new PushableProxyStorageTest_TestStorage($storage, $idGenerator);
    }

    /**
     * @param string $dir
     *
     * @return ArrayStorage
     */
    protected function newBaseStorage($data=[])
    {
        return new ArrayStorage($data);
    }
}

class PushableProxyStorageTest_TestStorage extends PushableProxyStorage
{
    /**
     * @return \Ems\Contracts\Core\Storage
     */
    public function getBaseStorage()
    {
        return $this->storage;
    }
}