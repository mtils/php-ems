<?php
/**
 *  * Created by mtils on 26.05.18 at 08:15.
 **/

namespace Ems\XType;


use DateTime;
use Ems\Contracts\Core\PointInTime;
use Ems\Contracts\XType\Formatter as FormatterContract;
use Ems\Contracts\Core\Formatter as CoreFormatterContract;
use Ems\IntegrationTest;
use Ems\Testing\Eloquent\MigratedDatabase;
use Ems\XType\Eloquent\Address;
use Ems\XType\Eloquent\Country;
use Ems\XType\Eloquent\ExtendedUser;
use Ems\XType\Eloquent\User;
use Ems\XType\UnitTypes\DistanceType;
use function func_get_args;
use function is_object;

include_once __DIR__.'/Eloquent/test_models.php';


class FormatterIntegrationTest extends IntegrationTest
{
    use MigratedDatabase;

    public function test_resolving()
    {
        $this->assertInstanceOf(FormatterContract::class, $this->formatter());
    }

    public function test_format_formats_by_type()
    {
        $formatter = $this->formatter();
        $created = new DateTime('2017-12-01 12:00:00');
        $birthday = new DateTime('1986-10-03');

        $user = ExtendedUser::unguarded(function () use ($created, $birthday) {
            return new ExtendedUser([
                'id'            => 32,
                'created_at'    => $created,
                'activated'     => true,
                'login_count'   => 18,
                'nickname'      => 'mike',
                'misc'          => [1,2,3],
                'birthday'      => $birthday
            ]);
        });

        $coreFormatter = $this->coreFormatter();

        $formatted = $formatter->format($user, 'created_at');
        $expected = $coreFormatter->dateTime($created);
        $this->assertEquals($expected, $formatted);

        $this->assertSame($formatter, $formatter->setTrueLangKey('posix.true'));
        $this->assertSame($formatter, $formatter->setFalseLangKey('posix.false'));

        $this->assertEquals('posix.true', $formatter->getTrueLangKey());
        $this->assertEquals('posix.false', $formatter->getFalseLangKey());

        $formatted = $formatter->format($user, 'activated');
        $this->assertEquals('posix.true', $formatted);

        $formatted = $formatter->format($user, 'login_count');
        $this->assertSame('18', $formatted);

        $formatted = $formatter->format($user, 'nickname');
        $this->assertSame('mike', $formatted);

        $formatted = $formatter->format($user, 'misc');
        $this->assertSame('NaN', $formatted);

        $formatted = $formatter->format($user, 'birthday');
        $expected = $coreFormatter->date($birthday);
        $this->assertEquals($expected, $formatted);


    }

    public function test_format_relative_temporal()
    {
        $formatter = $this->formatter();
        $month = DateTime::createFromFormat('Y-m-d', '2017-05-15');
        $type = new TemporalType([
            'precision' => PointInTime::MONTH,
            'absolute'  => false
        ]);

        $this->assertEquals('May', $formatter->value($type, $month));

        $month = DateTime::createFromFormat('Y-m-d', '2017-05-17');
        $type = new TemporalType([
            'precision' => PointInTime::WEEKDAY,
            'absolute'  => false
        ]);

        $this->assertEquals('Wednesday', $formatter->value($type, $month));

        $month = DateTime::createFromFormat('Y-m-d', '2017-05-15');
        $type = new TemporalType([
            'precision' => PointInTime::DAY,
            'absolute'  => false
        ]);

        $this->assertEquals('5/15', $formatter->value($type, $month));

        $month = DateTime::createFromFormat('Y-m-d H:i:s', '2017-05-15 10:23:15');
        $type = new TemporalType([
            'precision' => PointInTime::HOUR,
            'absolute'  => false
        ]);

        $this->assertEquals('10', $formatter->value($type, $month));

        $month = DateTime::createFromFormat('Y-m-d H:i:s', '2017-05-15 10:23:15');
        $type = new TemporalType([
            'precision' => PointInTime::MINUTE,
            'absolute'  => false
        ]);

        $this->assertEquals('10:23 am', $formatter->value($type, $month));

        $month = DateTime::createFromFormat('Y-m-d H:i:s', '2017-05-15 10:23:15');
        $type = new TemporalType([
            'precision' => PointInTime::SECOND,
            'absolute'  => false
        ]);

        $this->assertEquals('10:23 am', $formatter->value($type, $month));
    }

    public function test_format_formats_related_by_type()
    {
        $formatter = $this->formatter();
        $created = new DateTime('2017-12-01 12:00:00');

        $user = ExtendedUser::unguarded(function () use ($created) {
            return new ExtendedUser([
                'id' => 32,
                'created_at' => $created
            ]);
        });

        $address = Address::unguarded(function () {
            return new Address([
                'street' => 'Elm Street'
            ]);
        });

        $country = Country::unguarded(function () {
            return new Country([
                'name'     => 'England',
                'iso_code' => 'eng',
                'area'     => 244.15
            ]);
        });

        $address->country = $country;
        $user->address = $address;

        $formatted = $formatter->format($user, 'address.country.area');
        $expected = $this->coreFormatter()->unit($country->area, 'm²', 1);

        $this->assertEquals($expected, $formatted);

    }

    public function test_forLocale_switches_locale()
    {

        $formatter = $this->formatter();

        $coreFormatter = $this->coreFormatter();

        $created = new DateTime('2017-12-01 12:00:00');

        $user = ExtendedUser::unguarded(function () use ($created) {
            return new ExtendedUser([
                'id' => 32,
                'created_at' => $created
            ]);
        });

        $formatted = $formatter->format($user, 'created_at');
        $expected = $coreFormatter->dateTime($created);
        $this->assertEquals($expected, $formatted);

        $deFormatter = $formatter->forLocale('de_DE', 'en');

        $coreDeFormatter = $coreFormatter->forLocale('de_DE');

        $this->assertEquals('de_DE', $deFormatter->getLocale());

        $this->assertEquals($coreDeFormatter->dateTime($created), $deFormatter->format($user, 'created_at'));

        $this->assertEquals(['en'], $deFormatter->getFallbacks());
    }

    public function test_mapViewToVerbosity()
    {
        $formatter = $this->formatter();
        $this->assertSame($formatter, $formatter->mapViewToVerbosity('short', CoreFormatterContract::VERBOSE));
    }

    public function test_mapViewToVerbosity_throws_exception_on_unknown_verbosity()
    {
        $this->expectException(
            \Ems\Core\Exceptions\UnsupportedParameterException::class
        );
        $formatter = $this->formatter();
        $formatter->mapViewToVerbosity('short', 'foo');
    }

    public function test_format_formats_by_extension()
    {
        $formatter = $this->formatter();
        $created = new DateTime('2017-12-01 12:00:00');
        $birthday = new DateTime('1986-10-03');

        $user = ExtendedUser::unguarded(function () use ($created, $birthday) {
            return new ExtendedUser([
                'id'            => 32,
                'created_at'    => $created,
                'activated'     => true,
                'login_count'   => 18,
                'nickname'      => 'mike',
                'misc'          => [1,2,3],
                'birthday'      => $birthday
            ]);
        });

        $formatter->extend('temporal', function (TemporalType $type, DateTime $value, $view, $lang) {
           return $type->getName() . '|' . $value->format('Y') . "|$view|$lang";
        });

        $this->assertEquals('temporal|2017|default|en', $formatter->format($user, 'created_at'));

        // Second time just to see the cache hit in code coverage
        $this->assertEquals('temporal|2017|default|en', $formatter->format($user, 'created_at'));

    }

    public function test_format_formats_by_type_hierarchy_extension()
    {
        $formatter = $this->formatter();
        $created = new DateTime('2017-12-01 12:00:00');
        $birthday = new DateTime('1986-10-03');

        $user = ExtendedUser::unguarded(function () use ($created, $birthday) {
            return new ExtendedUser([
                'id'            => 32,
                'created_at'    => $created,
                'activated'     => true,
                'login_count'   => 18,
                'nickname'      => 'mike',
                'misc'          => [1,2,3],
                'birthday'      => $birthday,
                'height'        => 186,
                'distance_to_work' => 3600
            ]);
        });

        $formatter->extend(DistanceType::class, function (NumberType $type, $value, $view, $lang) {
            return $type->getName() .  "|$value|$view|$lang";
        });

        $this->assertEquals('length|186|default|en', $formatter->format($user, 'height'));

        // Second time just to see the cache hit in code coverage
        $this->assertEquals('distance|3600|default|en', $formatter->format($user, 'distance_to_work'));

    }

    /**
     *
     * @param string $locale
     *
     * @return Formatter
     */
    protected function formatter($locale='en')
    {
        /** @var FormatterContract $formatter */
        $formatter = $this->app(FormatterContract::class);
        return $formatter->setLocale($locale);
    }

    /**
     * @param string $locale
     *
     * @return CoreFormatterContract
     */
    protected function coreFormatter($locale='en')
    {
        /** @var CoreFormatterContract $formatter */
        $formatter = $this->app(CoreFormatterContract::class);
        return $formatter->setLocale($locale);
    }
}


