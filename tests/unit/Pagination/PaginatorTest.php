<?php
/**
 *  * Created by mtils on 06.01.18 at 07:40.
 **/

namespace Ems\Pagination;


use Ems\Contracts\Pagination\Pages;
use Ems\Core\Url;
use Ems\TestCase;
use Ems\Contracts\Pagination\Paginator as PaginatorContract;

class PaginatorTest extends TestCase
{
    public function test_implements_interface()
    {
        $this->assertInstanceOf(PaginatorContract::class, $this->paginate());
    }

    public function test___construct_and_setPagination()
    {
        $paginator = $this->paginate(2, 20, $this);
        $this->assertSame($this, $paginator->creator());
        $this->assertEquals(2, $paginator->getCurrentPageNumber());
        $this->assertEquals(20, $paginator->getPerPage());

        $this->assertSame($paginator, $paginator->setPagination(4, 12));
        $this->assertEquals(4, $paginator->getCurrentPageNumber());
        $this->assertEquals(12, $paginator->getPerPage());
    }


    public function test_defaults()
    {
        $originalPerPage = Paginator::getPerPageDefault();
        $originalPageName = Paginator::getDefaultPageParameterName();
        $originalSqueeze = Paginator::getDefaultSqueeze();
        $originalSpace = Paginator::getDefaultSqueezeSpace();

        Paginator::setPerPageDefault(20);
        Paginator::setDefaultPageParameterName('_page');

        $this->assertEquals(20, Paginator::getPerPageDefault());
        $this->assertEquals('_page', Paginator::getDefaultPageParameterName());

        $paginator = $this->paginate();

        $this->assertEquals(20, $paginator->getPerPage());
        $this->assertEquals('_page', $paginator->getPageParameterName());

        Paginator::setPerPageDefault($originalPerPage);
        Paginator::setDefaultPageParameterName($originalPageName);
        Paginator::setDefaultSqueeze($originalSqueeze);
        Paginator::setDefaultSqueezeSpace($originalSpace);

        $this->assertSame($paginator, $paginator->setPageParameterName('hansi'));
        $this->assertEquals('hansi', $paginator->getPageParameterName());

    }

    public function test_baseUrl()
    {
        $paginator = $this->paginate();
        $url = new Url('https://web-utils.de/products');

        $this->assertSame($paginator, $paginator->setBaseUrl($url));
        $this->assertSame($url, $paginator->getBaseUrl());
    }

    public function test_setResult_without_totalCount()
    {
        $paginator = $this->paginate();

        $result = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4]
        ];

        $this->assertSame($paginator, $paginator->setResult($result));
        $this->assertNull($paginator->getTotalCount());
        $this->assertFalse($paginator->hasTotalCount());
        $this->assertCount(count($result), $paginator);

        $this->assertEquals(['id' => 1], $paginator->first());
        $this->assertEquals(['id' => 4], $paginator->last());
    }

    public function test_getOffset()
    {
        $paginator = $this->paginate(2, 20);
        $this->assertEquals(20, $paginator->getOffset());
    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     */
    public function test_offsetSet_throws_exception()
    {
        $paginator = $this->paginate();

        $result = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4]
        ];

        $this->assertSame($paginator, $paginator->setResult($result));
        $pages = $paginator->pages();
        $pages[1] = 'foo';
    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnsupportedUsageException
     */
    public function test_offsetUnset_throws_exception()
    {
        $paginator = $this->paginate();

        $result = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
            ['id' => 4]
        ];

        $this->assertSame($paginator, $paginator->setResult($result));
        $pages = $paginator->pages();
        unset($pages[1]);
    }

    public function test_slice()
    {
        $result = range('a', 'z');
        $paginator = $this->paginate(1, 10);

        $slice = $paginator->slice($result);

        $this->assertCount(10, $slice);
        $this->assertEquals('a', $slice[0]);
        $this->assertEquals('j', $slice[9]);

        $slice = $paginator->setPagination(2)->slice($result);

        $this->assertCount(10, $slice);
        $this->assertEquals('k', $slice[0]);
        $this->assertEquals('t', $slice[9]);

        $slice = $paginator->setPagination(3)->slice($result);

        $this->assertCount(6, $slice);
        $this->assertEquals('u', $slice[0]);
        $this->assertEquals('z', $slice[5]);

    }

    public function test_pages_with_totalCount()
    {

        $result = range('a', 'z');
        $paginator = $this->paginate(1, 10);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertFalse($pages->isEmpty());
        $this->assertFalse($pages->hasOnlyOnePage());
        $this->assertSame($paginator, $pages->creator());
        $this->assertFalse($pages->isSqueezed());

        $array = [];

        foreach ($pages as $num=>$page) {
            $array[$num] = $page;
        }

        $this->assertCount(3, $array);


        $this->assertEquals((string)$url->query('page', 1), (string)$pages->first()->url());
        $this->assertSame($pages->first(), $pages[1]);
        $this->assertSame($pages->last(), $pages[3]);
        $this->assertEquals((string)$url->query('page', 3), (string)$pages->last()->url());

        $this->assertTrue($pages->offsetExists(1));
        $this->assertTrue($pages->offsetExists(2));
        $this->assertTrue($pages->offsetExists(3));
        $this->assertFalse($pages->offsetExists(0));

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertTrue($pages[1]->isCurrent());
        $this->assertFalse($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());

        $this->assertEquals(2, $pages[2]->number());
        $this->assertFalse($pages[2]->isFirst());
        $this->assertFalse($pages[2]->isCurrent());
        $this->assertFalse($pages[2]->isPrevious());
        $this->assertTrue($pages[2]->isNext());
        $this->assertFalse($pages[2]->isLast());

        $this->assertEquals(3, $pages[3]->number());
        $this->assertFalse($pages[3]->isFirst());
        $this->assertFalse($pages[3]->isCurrent());
        $this->assertFalse($pages[3]->isPrevious());
        $this->assertFalse($pages[3]->isNext());
        $this->assertTrue($pages[3]->isLast());

        $paginator->setPagination(2, 10);

        $pages = $paginator->pages();

        $this->assertSame($pages[2], $pages->current());

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertFalse($pages[1]->isCurrent());
        $this->assertTrue($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());

        $this->assertEquals(2, $pages[2]->number());
        $this->assertFalse($pages[2]->isFirst());
        $this->assertTrue($pages[2]->isCurrent());
        $this->assertFalse($pages[2]->isPrevious());
        $this->assertFalse($pages[2]->isNext());
        $this->assertFalse($pages[2]->isLast());

        $this->assertEquals(3, $pages[3]->number());
        $this->assertFalse($pages[3]->isFirst());
        $this->assertFalse($pages[3]->isCurrent());
        $this->assertFalse($pages[3]->isPrevious());
        $this->assertTrue($pages[3]->isNext());
        $this->assertTrue($pages[3]->isLast());

        $this->assertSame($pages[1], $pages->previous());
        $this->assertSame($pages[2], $pages->current());
        $this->assertSame($pages[3], $pages->next());


        $paginator->setPagination(3, 10);

        $pages = $paginator->pages();

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertFalse($pages[1]->isCurrent());
        $this->assertFalse($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());

        $this->assertEquals(2, $pages[2]->number());
        $this->assertFalse($pages[2]->isFirst());
        $this->assertFalse($pages[2]->isCurrent());
        $this->assertTrue($pages[2]->isPrevious());
        $this->assertFalse($pages[2]->isNext());
        $this->assertFalse($pages[2]->isLast());

        $this->assertEquals(3, $pages[3]->number());
        $this->assertFalse($pages[3]->isFirst());
        $this->assertTrue($pages[3]->isCurrent());
        $this->assertFalse($pages[3]->isPrevious());
        $this->assertFalse($pages[3]->isNext());
        $this->assertTrue($pages[3]->isLast());

    }

    public function test_pages_with_totalCount_and_perPage_for_two_pages()
    {

        $result = range('a', 'z');
        $paginator = $this->paginate(1, 20);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertFalse($pages->isEmpty());

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertTrue($pages[1]->isCurrent());
        $this->assertFalse($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());

        $this->assertEquals(2, $pages[2]->number());
        $this->assertFalse($pages[2]->isFirst());
        $this->assertFalse($pages[2]->isCurrent());
        $this->assertFalse($pages[2]->isPrevious());
        $this->assertTrue($pages[2]->isNext());
        $this->assertTrue($pages[2]->isLast());

        $paginator->setPagination(2, 20);

        $pages = $paginator->pages();

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertFalse($pages[1]->isCurrent());
        $this->assertTrue($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());

        $this->assertEquals(2, $pages[2]->number());
        $this->assertFalse($pages[2]->isFirst());
        $this->assertTrue($pages[2]->isCurrent());
        $this->assertFalse($pages[2]->isPrevious());
        $this->assertFalse($pages[2]->isNext());
        $this->assertTrue($pages[2]->isLast());

    }

    public function test_pages_with_totalCount_and_perPage_for_one_pages()
    {

        $result = range('a', 'z');
        $paginator = $this->paginate(1, 40);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertFalse($pages->isEmpty());

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertTrue($pages[1]->isCurrent());
        $this->assertFalse($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertTrue($pages[1]->isLast());

    }

    /**
     * @expectedException \Ems\Core\Exceptions\UnConfiguredException
     */
    public function test_asking_for_urls_when_none_assigned_throws_exception()
    {

        $result = range('a', 'z');
        $paginator = $this->paginate(1, 40);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $paginator->pages()->first()->url();

    }

    public function test_not_asking_for_urls_when_none_assigned_throws_no_exception()
    {

        $result = range('a', 'z');
        $paginator = $this->paginate(1, 40);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $this->assertEquals(1, $paginator->pages()->first()->number());

    }

    public function test_pages_without_totalCount()
    {
        $result = range('a', 'z');
        $paginator = $this->paginate(1, 10);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $items = $paginator->slice($result);
        $paginator->setResult($items);

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertFalse($pages->isEmpty());

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertTrue($pages[1]->isCurrent());
        $this->assertFalse($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());


        $paginator->setPagination(2, 10);
        $paginator->setResult($paginator->slice($result));

        $pages = $paginator->pages();

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertFalse($pages[1]->isCurrent());
        $this->assertTrue($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());

        $this->assertEquals(2, $pages[2]->number());
        $this->assertFalse($pages[2]->isFirst());
        $this->assertTrue($pages[2]->isCurrent());
        $this->assertFalse($pages[2]->isPrevious());
        $this->assertFalse($pages[2]->isNext());
        $this->assertFalse($pages[2]->isLast());

        $paginator->setPagination(3, 10);
        $paginator->setResult($paginator->slice($result));

        $pages = $paginator->pages();

        $this->assertEquals(1, $pages[1]->number());
        $this->assertTrue($pages[1]->isFirst());
        $this->assertFalse($pages[1]->isCurrent());
        $this->assertFalse($pages[1]->isPrevious());
        $this->assertFalse($pages[1]->isNext());
        $this->assertFalse($pages[1]->isLast());

        $this->assertEquals(2, $pages[2]->number());
        $this->assertFalse($pages[2]->isFirst());
        $this->assertFalse($pages[2]->isCurrent());
        $this->assertTrue($pages[2]->isPrevious());
        $this->assertFalse($pages[2]->isNext());
        $this->assertFalse($pages[2]->isLast());

        $this->assertEquals(3, $pages[3]->number());
        $this->assertFalse($pages[3]->isFirst());
        $this->assertTrue($pages[3]->isCurrent());
        $this->assertFalse($pages[3]->isPrevious());
        $this->assertFalse($pages[3]->isNext());
        $this->assertTrue($pages[3]->isLast());
    }

    public function test_pages_with_empty_result_and_totalCount()
    {

        $paginator = $this->paginate(1, 10);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $paginator->setResult([], 0);

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertTrue($pages->isEmpty());
        $this->assertCount(0, $pages);
        $this->assertFalse($pages->hasOnlyOnePage());
    }

    public function test_pages_with_empty_result_and_no_totalCount()
    {

        $paginator = $this->paginate(1, 10);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $paginator->setResult([]);

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertTrue($pages->isEmpty());
        $this->assertCount(0, $pages);
        $this->assertFalse($pages->hasOnlyOnePage());
    }

    public function test_pages_squeezed_to_start()
    {

        $result = range(1, 300000);
        $paginator = $this->paginate(1, 10);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertFalse($pages->isEmpty());
        $this->assertFalse($pages->hasOnlyOnePage());

        $this->assertTrue($pages->isSqueezed());

        $this->assertCount(11, $pages);
        $this->assertEquals(30001, $pages->totalPageCount());

        $this->assertTrue($pages[1]->isFirst());
        $this->assertTrue($pages[1]->isCurrent());

        $this->assertTrue($pages[2]->isNext());

        $this->assertFalse($pages[7]->isPlaceholder());
        $this->assertFalse($pages[8]->isPlaceholder());
        $this->assertTrue($pages[9]->isPlaceholder());

        $this->assertFalse($pages[10]->isLast());
        $this->assertTrue($pages[11]->isLast());


    }

    public function test_pages_squeezed_to_end()
    {

        $result = range(1, 300000);
        $paginator = $this->paginate(30000, 10);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertFalse($pages->isEmpty());
        $this->assertFalse($pages->hasOnlyOnePage());

        $this->assertTrue($pages->isSqueezed());

        $this->assertCount(11, $pages);
        $this->assertEquals(30001, $pages->totalPageCount());


        $this->assertTrue($pages[1]->isFirst());
        $this->assertFalse($pages[1]->isCurrent());

        $this->assertFalse($pages[2]->isPlaceholder());
        $this->assertTrue($pages[3]->isPlaceholder());
        $this->assertFalse($pages[4]->isPlaceholder());

        $this->assertTrue($pages[9]->isPrevious());

        $this->assertTrue($pages[10]->isCurrent());

        $this->assertFalse($pages[10]->isLast());
        $this->assertTrue($pages[11]->isLast());


    }

    public function test_pages_squeezed_to_center()
    {

        $result = range(1, 300000);
        $paginator = $this->paginate(15000, 10);

        $url = new Url('https://web-utils.de/products');
        $paginator->setBaseUrl($url);

        $items = $paginator->slice($result);
        $paginator->setResult($items, count($result));

        $pages = $paginator->pages();

        $this->assertInstanceOf(Pages::class, $pages);
        $this->assertFalse($pages->isEmpty());
        $this->assertFalse($pages->hasOnlyOnePage());

        $this->assertTrue($pages->isSqueezed());

        $dumps = [];

        foreach ($pages as $page) {
            $dumps[] = $page->dump();
        }

        $this->assertCount(12, $pages);
        $this->assertEquals(30001, $pages->totalPageCount());

        $this->assertTrue($pages[1]->isFirst());
        $this->assertFalse($pages[1]->isCurrent());

        $this->assertTrue($pages[2]->isPlaceholder());
        $this->assertFalse($pages[3]->isPlaceholder());
        $this->assertTrue($pages[11]->isPlaceholder());
        $this->assertFalse($pages[12]->isPlaceholder());

        $this->assertTrue($pages[6]->isPrevious());

        $this->assertTrue($pages[7]->isCurrent());
        $this->assertTrue($pages[8]->isNext());

        $this->assertFalse($pages[11]->isLast());
        $this->assertTrue($pages[12]->isLast());


    }

    protected function paginate($currentPage=1, $perPage=null, $creator=null)
    {
        return new Paginator($currentPage, $perPage, $creator);
    }
}