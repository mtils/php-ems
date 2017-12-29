<?php
/**
 *  * Created by mtils on 14.12.17 at 16:53.
 **/

namespace Ems\Model;


use Ems\Contracts\Model\OrmCollection;

class GenericOrmCollection extends GenericPaginatableResult implements OrmCollection
{
    use OrmCollectionMethods;
}