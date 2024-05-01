<?php

namespace Ems\Events;

use Ems\Contracts\Core\Errors\ConfigurationError;
use Ems\Contracts\Core\Errors\UnSupported;
use InvalidArgumentException;

class MatchingListenerTest extends \Ems\TestCase
{
    public function test_it_instantiates()
    {
        $this->assertInstanceOf(
            MatchingListener::class,
            $this->newListener()
        );
    }

    public function test_setPattern_and_getPattern()
    {
        $listener = $this->newListener(null, 'users.*');
        $this->assertEquals('users.*', $listener->getPattern());
        $this->assertSame($listener, $listener->setPattern('*.updated'));
        $this->assertEquals('*.updated', $listener->getPattern());
    }

    public function test_getMarkFilter_and_setMarkFilter()
    {
        $markFilter = ['source' => 'import'];
        $markFilter2 = ['source' => 'api'];

        $listener = $this->newListener(null, '*', $markFilter);
        $this->assertEquals($markFilter, $listener->getMarkFilter());
        $this->assertSame($listener, $listener->setMarkFilter($markFilter2));
        $this->assertEquals($markFilter2, $listener->getMarkFilter());
    }

    public function test_matchesPattern_with_different_patterns()
    {

        $tests = [
            '*' => [
                'users.updated'         => true,
                'users.private.updated' => true
            ],
            'users.*' => [
                'users.updated'         => true,
                'users.private.updated' => true,
                'address.updated'       => false
            ],
            'orm.users.*' => [
                'orm.users.updated'     => true,
                'orm.addresses.updated' => false,
                'address.updated'       => false
            ],
            'orm.*.updated' => [
                'orm.users.updated'     => true,
                'orm.addresses.updated' => true,
                'orm.addresses.created' => false,
                'api.users.updated'     => false,
                'orm.updated'           => false,
            ]
        ];

        foreach ($tests as $pattern=>$data) {

            $listener = $this->newListener(null, $pattern);

            foreach ($data as $event=>$result) {

                $listener = $this->newListener(null, $pattern);

                $this->assertSame($result, $listener->matchesPattern($event));
            }
        }
    }

    public function test_matchesMarks_with_empty_filter()
    {

        $listener = $this->newListener();

        $this->assertTrue($listener->matchesMarks(['no-broadcast' => true]));

        $this->assertTrue($listener->matchesMarks([]));

        $this->assertTrue($listener->matchesMarks(['no-broadcast' => false]));

    }

    public function test_matchesMarks_with_true_filter()
    {

        $listener = $this->newListener()->setMarkFilter([
            'no-broadcast' => true
        ]);

        $this->assertTrue($listener->matchesMarks(['no-broadcast' => true]));

        $this->assertFalse($listener->matchesMarks([]));

        $this->assertFalse($listener->matchesMarks(['no-broadcast' => false]));

    }

    public function test_matchesMarks_with_false_filter()
    {

        $listener = $this->newListener()->setMarkFilter([
            'no-broadcast' => false
        ]);

        $this->assertFalse($listener->matchesMarks(['no-broadcast' => true]));

        $this->assertTrue($listener->matchesMarks([]));

        $this->assertTrue($listener->matchesMarks(['no-broadcast' => false]));

    }

    public function test_matchesMarks_with_equals_filter()
    {

        $listener = $this->newListener()->setMarkFilter([
            'source' => 'api'
        ]);

        $this->assertFalse($listener->matchesMarks(['source' => 'import']));

        $this->assertFalse($listener->matchesMarks([]));

        $this->assertTrue($listener->matchesMarks(['source' => 'api']));

    }

    public function test_matchesMarks_with_unequals_filter()
    {

        $listener = $this->newListener()->setMarkFilter([
            'source' => '!api'
        ]);

        $this->assertTrue($listener->matchesMarks(['source' => 'import']));

        $this->assertTrue($listener->matchesMarks([]));

        $this->assertFalse($listener->matchesMarks(['source' => 'api']));

    }

    public function test_matchesMarks_with_multple_filters()
    {

        $listener = $this->newListener()->setMarkFilter([
            'source'       => '!api',
            'from-remote'  => true,
            'from-batch'   => false
        ]);

        // I want to be called, if not from api triggered AND
        // if it IS from a remote server AND
        // if it is NOT from a batch action

        $this->assertTrue($listener->matchesMarks([
            'source' => 'import',
            'from-remote' => true,
            'from-batch' => false
        ]));

        $this->assertTrue($listener->matchesMarks([
            'from-remote' => true,
            'from-batch' => false
        ]));

        $this->assertTrue($listener->matchesMarks([
            'from-remote' => true
        ]));

        $this->assertFalse($listener->matchesMarks([
            'source' => 'api',
            'from-remote' => true,
            'from-batch' => false
        ]));

        $this->assertFalse($listener->matchesMarks([
            'source' => 'import',
            'from-batch' => false
        ]));

        $this->assertFalse($listener->matchesMarks([
            'from-remote' => true,
            'from-batch' => true
        ]));
    }

    public function test_invoke_throws_exception_if_no_callable_assigned()
    {
        $this->expectException(ConfigurationError::class);
        $listener = $this->newListener(null);
        $listener();
    }

    public function test_toArray_throws_exception_if_exclamation_mark_in_associative_marks()
    {
        $this->expectException(InvalidArgumentException::class);
        $listener = $this->newListener(null);
        $listener->markToArray(['!no-broadcast' => true]);
    }


    public function test_toArray_turns_leading_exclamation_marks_to_false_values()
    {
        $listener = $this->newListener(null);
        $this->assertEquals([
            'remote' => false
        ],$listener->markToArray('!remote'));
    }

    public function test_toArray_throws_exception_if_value_no_string_or_boolean()
    {
        $this->expectException(UnSupported::class);
        $listener = $this->newListener(null);
        $listener->markToArray(['no-broadcast' => 12]);
    }

    public function test_toArray_throws_exception_if_mark_not_in_known_marks()
    {
        $this->expectException(UnSupported::class);
        $listener = $this->newListener(null);
        $listener->markToArray('no-broadcast', null, ['remote'=>true]);
    }

    protected function newListener(callable $f=null, $pattern='*', array $markFilter=[])
    {
        return new MatchingListener($f, $pattern, $markFilter);
    }
}
