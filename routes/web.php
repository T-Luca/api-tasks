<?php

define("API_VERSION", 'v1');
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version() . ' - ' . 'Current API version: ' . API_VERSION;
});

/** CORS */
$router->options(
    '/{any:.*}', [
    'middleware' => ['cors'],
    function () {
        return response('OK', 200);
    }
]);

/** Routes that doesn't require auth */
$router->group(['namespace' => API_VERSION, 'prefix' => API_VERSION, 'middleware' => 'cors'], function () use ($router) {
    $router->post('/login', ['uses' => 'UserController@login']);
    $router->post('/register', ['uses' => 'UserController@register']);
    $router->post('/forgot-password', ['uses' => 'UserController@forgotPassword']);
    $router->post('/change-password', ['uses' => 'UserController@changePassword']);
});

/** Routes with auth */
$router->group(['namespace' => API_VERSION, 'prefix' => API_VERSION, 'middleware' => 'cors|jwt'], function () use ($router) {
    $router->group(['prefix' => 'user'], function () use ($router) {
        $router->get('/', ['uses' => 'UserController@get']);
        $router->patch('/', ['uses' => 'UserController@update']);
    });
    // user tasks
    $router->group(['prefix' => 'task'], function () use ($router) {
        $router->patch('/', ['uses' => 'UserController@updateTask']);
    });

    $router->group(['prefix' => 'admin', 'middleware' => 'admin'], function () use ($router) {
        $router->get('/users', ['uses' => 'AdminController@getUsers']);
        $router->group(['prefix' => 'user'], function () use ($router) {
            $router->post('/', ['uses' => 'AdminController@createUser']);
            $router->patch('/{id}', ['uses' => 'AdminController@updateUser']);
            $router->delete('/{id}', ['uses' => 'AdminController@deleteUser']);
        });
        //admin tasks
        $router->get('/tasks', ['uses' => 'TaskController@getTasks']);
        $router->group(['prefix' => 'task'], function () use ($router) {
            $router->post('/', ['uses' => 'TaskController@createTask']);
            $router->patch('/{id}', ['uses' => 'TaskController@updateTask']);
            $router->delete('/{id}', ['uses' => 'TaskController@deleteTask']);
        });
    });

    $router->post('/addComment/{id}',['uses' => 'UserController@addComment']);
});