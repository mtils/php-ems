<?php
/*
|--------------------------------------------------------------------------
| Register The Composer Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader
| for our application. We just need to utilize it! We'll require it
| into the script here so that we do not have to worry about the
| loading of any our classes "manually". Feels great to relax.
|
*/
require __DIR__.'/vendor/autoload.php';

error_reporting(E_ALL | E_DEPRECATED);

/*
|--------------------------------------------------------------------------
| Set The Default Timezone
|--------------------------------------------------------------------------
|
| Here we will set the default timezone for PHP. PHP is notoriously mean
| if the timezone is not explicitly set. This will be used by each of
| the PHP date and date-time functions throughout the application.
|
*/
date_default_timezone_set('UTC');


require __DIR__.'/tests/helpers/TestCase.php';

require __DIR__.'/tests/helpers/traits/AppTrait.php';
require __DIR__.'/tests/helpers/traits/LaravelAppTrait.php';
require __DIR__.'/tests/helpers/traits/TestData.php';
require __DIR__.'/tests/helpers/IntegrationTest.php';
require __DIR__.'/tests/helpers/HttpMockTest.php';
require __DIR__.'/tests/helpers/LaravelIntegrationTest.php';
require __DIR__.'/tests/helpers/DatabaseIntegrationTest.php';
require __DIR__.'/tests/helpers/OrmIntegrationTest.php';
require __DIR__.'/tests/helpers/traits/RoutingTrait.php';

require __DIR__.'/tests/helpers/traits/AssetsFactoryMethods.php';
