<?php
/**
 *  * Created by mtils on 21.11.2021 at 13:54.
 **/

namespace Ems\Model\Eloquent;

use Illuminate\Database\Connectors\ConnectionFactory;

class SharedPdoConnectionFactory extends ConnectionFactory
{

    protected function createPdoResolver(array $config)
    {
        if (isset($config['pdo']) && $config['pdo']) {
            return $config['pdo'];
        }
        return parent::createPdoResolver($config);
    }

}