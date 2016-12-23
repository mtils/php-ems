<?php

namespace Ems\Assets;

use Ems\Core\LocalFilesystem;
use Ems\Core\ManualMimeTypeProvider;
use Ems\Contracts\Assets\NameAnalyser;
use Ems\Contracts\Assets\Registry as RegistryContract;
use Ems\Testing\LoggingCallable;
use Ems\Testing\Cheat;

class RegistryTest extends \Ems\TestCase
{
    use AssetsFactoryMethods;

    public function test_implements_interface()
    {
        $this->assertInstanceOf(
            'Ems\Contracts\Assets\Registry',
            $this->newRegistry()
        );
    }

    public function test_returns_itself_on_import()
    {
        $registry = $this->newRegistry();
        $this->assertSame($registry, $registry->import('jquery.js'));
    }

    public function test_groups_returns_assigned_groups()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js');
        $registry->import('bootstrap.css');

        $this->assertEquals(['js', 'css'], $registry->groups());
    }

    public function test_groups_with_same_suffix_returns_separate_groups()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.css', 'base.css');
        $registry->import('bootstrap.css', 'extended.css');

        $this->assertEquals(['base.css', 'extended.css'], $registry->groups());
    }

    public function test_assets_with_same_name_from_different_groups_are_not_assumed_as_already_added()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.css', 'base.css');
        $registry->import('jquery.css', 'extended.css');

        $this->assertEquals(['base.css', 'extended.css'], $registry->groups());
    }

    public function test_count_returns_count_of_assigned_assets()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js');
        $registry->import('bootstrap.css');

        $this->assertCount(2, $registry);
    }

    public function test_import_does_not_import_doubles()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js');
        $registry->import('bootstrap.css');

        $this->assertCount(2, $registry);

        $this->assertSame($registry, $registry->import('jquery.js'));
        $registry->import('bootstrap.css');

        $this->assertCount(2, $registry);
    }

    public function test_newAssert_assings_group_uri_and_mimetype()
    {
        $registry = $this->newRegistry();

        $assert = $registry->newAsset('jquery.js');
        $this->assertEquals('js', $assert->group());
        $this->assertEquals('application/javascript', $assert->mimeType());
        $this->assertEquals('http://localhost/js/jquery.js', $assert->uri());
    }

    public function test_customHandler_is_called_and_skips_add()
    {
        $registry = $this->newRegistry();
        $handler = new LoggingCallable();

        $registry->on('jquery.js', $handler);

        $registry->import('jquery.js');

        $this->assertCount(1, $handler);

        $this->assertCount(0, $registry);

        $this->assertInstanceOf(get_class($registry), $handler->arg(0));
        $this->assertEquals('jquery.js', $handler->arg(1));
        $this->assertEquals('js', $handler->arg(2));
    }

    public function test_import_from_customHandler_dont_lead_to_endless_recursion()
    {
        $registry = $this->newRegistry();

        $registry->on('jquery.contextMenu.js', function ($registry, $asset, $group) {
            $registry->import('jquery.js');
            $registry->import('jquery.contextMenu.js');
        });

        $registry->import('jquery.contextMenu.js');

        $this->assertCount(2, $registry);

        $this->assertEmpty(Cheat::get($registry, 'skipHandler'));
    }

    public function test_inline_adds_import()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js');
        $registry->inline('search.js', "$('table.search-result td').click(function(){});");

        $this->assertCount(2, $registry);

        $this->assertSame($registry, $registry->import('jquery.js'));

        $this->assertCount(2, $registry);
        $this->assertEquals(['js'], $registry->groups());
    }

    public function test_import_multiple_asserts_at_once()
    {
        $registry = $this->newRegistry();


        $registry->import(['jquery.js', 'jquery.select2.js']);

        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
    }

    public function test_import_assert_with_attributes()
    {
        $registry = $this->newRegistry();


        $registry->import(['name'=> 'print.css', 'media'=>'print']);

        $assert = $registry['css']->first();

        $this->assertEquals('print.css', $assert->name());
        $this->assertEquals(['media' => 'print'], $assert->attributes());

//         $this->assertEquals(0, $this->index($js, 'jquery.js'));
//         $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
    }

    public function test_offsetExists_returns_true_if_group_exists_and_false_if_not()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js');
        $registry->inline('search.js', "$('table.search-result td').click(function(){});");

        $this->assertTrue(isset($registry['js']));
        $this->assertFalse(isset($registry['less']));
    }

    /**
     * @expectedException \BadMethodCallException
     **/
    public function test_offsetSet_throws_BadMethodCallException()
    {
        $registry = $this->newRegistry();

        $registry['js'] = 'Crash';
    }

    /**
     * @expectedException \BadMethodCallException
     **/
    public function test_offsetUnset_throws_BadMethodCallException()
    {
        $registry = $this->newRegistry();

        unset($registry['js']);
    }

    /**
     * @expectedException \RuntimeException
     **/
    public function test_before_without_previous_import_throws_RuntimeException()
    {
        $registry = $this->newRegistry();

        $registry->before('jquery.js');
    }

    /**
     * @expectedException \RuntimeException
     **/
    public function test_after_without_previous_import_throws_RuntimeException()
    {
        $registry = $this->newRegistry();

        $registry->after('jquery.js');
    }

    public function test_offsetGet_returns_assigned_collection()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js');
        $registry->import('jquery.contextMenu.js');
        $registry->import('bootstrap.css');

        $js = $registry['js'];
        $this->assertInstanceOf('Ems\Contracts\Assets\Collection', $js);
        $this->assertCount(2, $js);

        $css = $registry['css'];
        $this->assertInstanceOf('Ems\Contracts\Assets\Collection', $css);
        $this->assertCount(1, $css);
    }

    /**
     * @expectedException \OutOfBoundsException
     **/
    public function test_offsetGet_throws_OutOfBoundsException_if_group_not_found()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js');
        $registry->import('jquery.contextMenu.js');
        $registry->import('bootstrap.css');

        $less = $registry['less'];
    }

    /**
     * @expectedException \OutOfBoundsException
     **/
    public function test_offsetGet_throws_OutOfBoundsException_if_mimeType_not_found()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.foo');
//         $foo= $registry['foo'];
    }

    public function test_offsetGet_assigns_mimeType()
    {
        $registry = $this->newRegistry();

        $registry->import('bootstrap.css');
        $this->assertEquals('text/css', $registry['css']->mimeType());
    }

    public function test_offsetGet_assigns_mimeType_by_group_if_not_found_in_name()
    {
        $registry = $this->newRegistry();

        $registry->import('http://fonts.googleapis.com/css?family=Roboto:400,300', 'css');
        $this->assertEquals('text/css', $registry['css']->mimeType());
    }

    public function test_before_returns_registry()
    {
        $registry = $this->newRegistry();
        $this->assertSame($registry, $registry->import('jquery.js')->before('jquery.contextMenu.js'));
    }

    public function test_after_returns_registry()
    {
        $registry = $this->newRegistry();
        $this->assertSame($registry, $registry->import('jquery.js')->after('jquery.contextMenu.js'));
    }

    public function test_before_forces_asset_to_be_sorted_before()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.select2.js');
        $registry->import('jquery.js')->before('jquery.select2.js');
        $registry->import('jquery.contextMenu.js')->before('jquery.select2.js');

        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.contextMenu.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.select2.js'));
    }

    public function test_after_forces_asset_to_be_sorted_after()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.select2.js')->after('jquery.js');
        $registry->import('jquery.contextMenu.js')->after('jquery.select2.js');
        $registry->import('jquery.js');

        $js = $registry['js'];

        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.contextMenu.js'));
    }

    public function test_before_in_same_order_as_assigned_dont_change_ordering()
    {
        $registry = $this->newRegistry();

        $registry->import('jquery.js')->before('jquery.select2.js');
        $registry->import('jquery.contextMenu.js')->before('jquery.select2.js');
        $registry->import('jquery.select2.js');

        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.contextMenu.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.select2.js'));
    }

    public function test_after_in_same_order_as_assigned_dont_change_ordering()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.js');
        $registry->import('jquery.select2.js')->after('jquery.js');
        $registry->import('jquery.contextMenu.js')->after('jquery.select2.js');

        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.contextMenu.js'));
    }

    public function test_after_dont_add_to_all_collections_when_same_names_in_multiple_groups()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.contextMenu.js')->after('jquery.js');

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');

        $registry->import('jquery.js', 'second.js');


        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.contextMenu.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.select2.js'));

        $secondJs = $registry['second.js'];
        $this->assertEquals(0, $this->index($secondJs, 'jquery.js'));
        $this->assertCount(1, $secondJs); // If count is 2 contextMenu is also added to this collection
    }

    public function test_before_dont_add_to_all_collections_when_same_names_in_multiple_groups()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.contextMenu.js')->before('jquery.js');

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');

        $registry->import('jquery.js', 'second.js');


        $js = $registry['js'];

        $this->assertEquals(0, $this->index($js, 'jquery.contextMenu.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.select2.js'));

        $secondJs = $registry['second.js'];
        $this->assertEquals(0, $this->index($secondJs, 'jquery.js'));
        $this->assertCount(1, $secondJs); // If count is 2 contextMenu is also added to this collection
    }

    public function test_after_adds_to_collection_with_same_group()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.contextMenu.js')->after();

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');

        $registry->import('jquery.css');


        $js = $registry['js'];

        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.contextMenu.js'));

        $css = $registry['css'];

        $this->assertEquals(0, $this->index($css, 'jquery.css'));
    }

    public function test_before_adds_to_collection_with_same_group()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.contextMenu.css')->before();

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');

        $registry->import('jquery.css');


        $js = $registry['js'];

        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));

        $css = $registry['css'];

        $this->assertEquals(0, $this->index($css, 'jquery.contextMenu.css'));
        $this->assertEquals(1, $this->index($css, 'jquery.css'));
    }

    public function test_insert_order_has_higher_priority_than_after()
    {
        $registry = $this->newRegistry();

        $registry->import('fastclick.js')->after('jquery.contextMenu.js');

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');
        $registry->import('jquery.contextMenu.js');


        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.contextMenu.js'));
        $this->assertEquals(3, $this->index($js, 'fastclick.js'));
    }

    public function test_import_multiple_assets_at_once_in_one_group()
    {
        $registry = $this->newRegistry();

        $registry->import(['jquery.js', 'jquery.select2.js', 'jquery.contextMenu.js'], 'base');

        $js = $registry['base'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.contextMenu.js'));
    }

    public function test_import_multiple_assets_after_adds_in_right_order()
    {
        $registry = $this->newRegistry();

        $registry->import(['fastclick.js', 'scrollwheel.js', 'waypoints.js'])
                 ->after('jquery.contextMenu.js');

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');
        $registry->import('jquery.contextMenu.js');


        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.contextMenu.js'));
        $this->assertEquals(3, $this->index($js, 'fastclick.js'));
        $this->assertEquals(4, $this->index($js, 'scrollwheel.js'));
        $this->assertEquals(5, $this->index($js, 'waypoints.js'));
    }

    public function test_import_multiple_assets_before_adds_in_right_order()
    {
        $registry = $this->newRegistry();

        $registry->import(['fastclick.js', 'scrollwheel.js', 'waypoints.js'])
                 ->before('jquery.select2.js');

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');
        $registry->import('jquery.contextMenu.js');


        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'fastclick.js'));
        $this->assertEquals(2, $this->index($js, 'scrollwheel.js'));
        $this->assertEquals(3, $this->index($js, 'waypoints.js'));
        $this->assertEquals(4, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(5, $this->index($js, 'jquery.contextMenu.js'));
    }

    public function test_import_multiple_assets_before_any_adds_in_right_order()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');
        $registry->import('jquery.contextMenu.js');

        $registry->import(['fastclick.js', 'scrollwheel.js', 'waypoints.js'])
                 ->before();


        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'fastclick.js'));
        $this->assertEquals(1, $this->index($js, 'scrollwheel.js'));
        $this->assertEquals(2, $this->index($js, 'waypoints.js'));
        $this->assertEquals(3, $this->index($js, 'jquery.js'));
        $this->assertEquals(4, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(5, $this->index($js, 'jquery.contextMenu.js'));
    }

    public function test_import_multiple_assets_after_any_adds_in_right_order()
    {
        $registry = $this->newRegistry();

        $registry->import(['fastclick.js', 'scrollwheel.js', 'waypoints.js'])
                 ->after();

        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');
        $registry->import('jquery.contextMenu.js');


        $js = $registry['js'];
        $this->assertEquals(0, $this->index($js, 'jquery.js'));
        $this->assertEquals(1, $this->index($js, 'jquery.select2.js'));
        $this->assertEquals(2, $this->index($js, 'jquery.contextMenu.js'));
        $this->assertEquals(3, $this->index($js, 'fastclick.js'));
        $this->assertEquals(4, $this->index($js, 'scrollwheel.js'));
        $this->assertEquals(5, $this->index($js, 'waypoints.js'));
    }

    public function test_getIterator_returns_groups_and_collections()
    {
        $registry = $this->newRegistry();


        $registry->import('jquery.js');
        $registry->import('jquery.select2.js');
        $registry->import('jquery.contextMenu.js');
        $registry->import('app.less');
        $registry->import('custom.css');
        $registry->import('layout.css');
        $registry->import('reset.css');

        $all = $this->all($registry);

        $this->assertEquals(['js', 'less', 'css'], array_keys($all));

        $this->assertCount(3, $all['js']);
        $this->assertCount(1, $all['less']);
        $this->assertCount(3, $all['css']);

        foreach ($all as $group=>$collection) {
            $this->assertInstanceOf('Ems\Contracts\Assets\Collection', $collection);
        }
    }
}
