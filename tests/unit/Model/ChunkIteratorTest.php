<?php
/**
 *  * Created by mtils on 25.07.20 at 11:14.
 **/

namespace unit\Model;


use Ems\IntegrationTest;
use Ems\Model\ChunkIterator;
use Ems\TestCase;
use Iterator;

use function array_slice;
use function func_get_args;
use function iterator_to_array;
use function print_r;
use function range;

class ChunkIteratorTest extends TestCase
{
    /**
     * @test
     */
    public function it_implements_iterator()
    {
        $paginatable = function () {};
        $this->assertInstanceOf(Iterator::class, $this->newIterator($paginatable));
    }

    /**
     * @test
     */
    public function it_produces_chunks()
    {
        $alphabet = range('A', 'Z');
        $calls = [];

        $paginator = function ($offset, $chunkSize) use ($alphabet, &$calls) {
            $calls[] = func_get_args();
            return array_slice($alphabet, $offset, $chunkSize);
        };

        $result = iterator_to_array($this->newIterator($paginator));

        $awaitedCalls = [
            [ 0,10],
            [10,10],
            [20,10]
        ];

        $this->assertEquals($alphabet, $result);
        $this->assertEquals($awaitedCalls, $calls);

    }

    /**
     * @test
     */
    public function it_produces_chunks_with_result_with_exact_multiplier()
    {
        $alphabet = range('A', 'T');
        $calls = [];

        $paginator = function ($offset, $chunkSize) use ($alphabet, &$calls) {
            $calls[] = func_get_args();
            return array_slice($alphabet, $offset, $chunkSize);
        };

        $result = iterator_to_array($this->newIterator($paginator));

        // Without a count the class cannot now when the result is "finished"
        $awaitedCalls = [
            [ 0,10],
            [10,10],
            [20,10]
        ];

        $this->assertEquals($alphabet, $result);
        $this->assertEquals($awaitedCalls, $calls);

    }

    /**
     * @test
     */
    public function it_produces_chunks_with_result_with_one_chunk()
    {
        $alphabet = range('A', 'J');
        $calls = [];

        $paginator = function ($offset, $chunkSize) use ($alphabet, &$calls) {
            $calls[] = func_get_args();
            return array_slice($alphabet, $offset, $chunkSize);
        };

        $result = iterator_to_array($this->newIterator($paginator));

        $awaitedCalls = [
            [ 0,10],
            [10,10]
        ];

        $this->assertEquals($alphabet, $result);
        $this->assertEquals($awaitedCalls, $calls);

    }

    /**
     * @test
     */
    public function it_produces_chunks_with_result_less_than_one_chunk()
    {
        $alphabet = range('A', 'I');
        $calls = [];

        $paginator = function ($offset, $chunkSize) use ($alphabet, &$calls) {
            $calls[] = func_get_args();
            return array_slice($alphabet, $offset, $chunkSize);
        };

        $result = iterator_to_array($this->newIterator($paginator));

        $awaitedCalls = [
            [ 0,10]
        ];

        $this->assertEquals($alphabet, $result);
        $this->assertEquals($awaitedCalls, $calls);

    }

    /**
     * @test
     */
    public function it_produces_chunks_with_result_less_empty_list()
    {
        $alphabet = [];
        $calls = [];

        $paginator = function ($offset, $chunkSize) use ($alphabet, &$calls) {
            $calls[] = func_get_args();
            return [];
        };

        $result = iterator_to_array($this->newIterator($paginator));

        $awaitedCalls = [
            [ 0,10]
        ];

        $this->assertEquals($alphabet, $result);
        $this->assertEquals($awaitedCalls, $calls);

    }

    protected function newIterator($paginatable, $chunkSize=10)
    {
        return new ChunkIterator($paginatable, $chunkSize);
    }
}