<?php
/**
 *  * Created by mtils on 13.07.19 at 08:43.
 **/

return [
    [
        'uri'    => 'users',
        'method' => 'GET',
        'pattern' => 'users',
        'handler' => 'UserController@index',
        'parameters' => [],
        'name' => 'users.index'
    ],
    [
        'uri'    => 'users',
        'method' => 'POST',
        'pattern' => 'users',
        'handler' => 'UserController@store',
        'parameters' => [],
        'name' => 'users.store'
    ],
    [
        'uri'    => 'users/create',
        'method' => 'GET',
        'pattern' => 'users/create',
        'handler' => 'UserController@create',
        'parameters' => [],
        'name' => 'users.create'
    ],
    [
        'uri'    => 'users/12',
        'method' => 'GET',
        'pattern' => 'users/{user_id}',
        'handler' => 'UserController@show',
        'parameters' => ['user_id' => 12],
        'name' => 'users.show'
    ],
    [
        'uri'    => 'users/22/edit',
        'method' => 'GET',
        'pattern' => 'users/{user_id}/edit',
        'handler' => 'UserController@edit',
        'parameters' => ['user_id' => 22],
        'name' => 'users.edit'
    ],
    [
        'uri'    => 'users/22',
        'method' => 'PUT',
        'pattern' => 'users/{user_id}',
        'handler' => 'UserController@update',
        'parameters' => ['user_id' => 22],
        'name' => 'users.update'
    ],
    [
        'uri'    => 'users/480',
        'method' => 'DELETE',
        'pattern' => 'users/{user_id}',
        'handler' => 'UserController@destroy',
        'parameters' => ['user_id' => 480],
        'name' => 'users.destroy'
    ],
    [
        'uri'    => 'users/35988/parent/65/addresses/177849',
        'method' => 'GET',
        'pattern' => 'users/{user_id}/parent/{parent_id}/addresses/{address_id}',
        'handler' => 'UserAddresssController@editParent',
        'parameters' => ['user_id' => 35988, 'parent_id' => 65, 'address_id' => 177849],
        'name' => 'users.parent.addresses.show'
    ]
];