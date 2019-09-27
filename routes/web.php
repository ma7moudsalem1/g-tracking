<?php


$router->get('/', function () use ($router) {
    return $router->app->version();
});

// Users
$router->group(['namespace' => 'User'], function () use ($router) {

    $router->group(['namespace' => 'Auth'], function() use ($router) {

        $router->post('auth/register', ['uses' => 'RegisterController@register', 'as' => 'register']);
        $router->post('auth/login', ['uses' => 'AuthController@authenticate', 'as' => 'login']);
    });


    $router->group(['middleware' => 'jwt.auth'], function() use ($router) {

        $router->group(['namespace' => 'Invite', 'prefix' => 'invite'], function() use ($router) {

            $router->post('employee', ['uses' => 'InvitationController@sendEmployeeInvitation', 'as' => 'employee.invite']);
            $router->post('group', ['uses' => 'InvitationController@sendInvitationGroup', 'as' => 'group.invite']);
            $router->post('group/accept', ['uses' => 'InvitationController@acceptGroupInvitation', 'as' => 'accept.group.invite']);
            $router->post('group/code', ['uses' => 'InvitationController@createGroupCodeForInvite', 'as' => 'code.group.invite']);
            $router->post('group/code/accept', ['uses' => 'InvitationController@AcceptGroupCodeForInvite', 'as' => 'code.accept.group.invite']);
       
        });

        $router->group(['namespace' => 'Group', 'prefix' => 'group'], function() use ($router) {

            $router->get('all', ['uses' => 'GroupController@index', 'as' => 'group.list']);
            $router->get('show/{id}', ['uses' => 'GroupController@show', 'as' => 'group.group']);
            $router->post('create', ['uses' => 'GroupController@store', 'as' => 'group.store']);
            $router->put('update/{id}', ['uses' => 'GroupController@update', 'as' => 'group.update']);
            $router->delete('delete/{id}', ['uses' => 'GroupController@destroy', 'as' => 'group.destroy']);
       
        });

        $router->group(['namespace' => 'Profile', 'prefix' => 'profile'], function() use ($router) {

            $router->get('data', ['uses' => 'ProfileController@index', 'as' => 'profile.data']);
       
        });

    });

});
