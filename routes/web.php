<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('gmp','GmpController@index');
Route::get('hswebhook','GmpController@hswebhook');
Route::post('hswebhook','GmpController@hswebhook');

Route::group(['middleware' => ['web', 'auth']], function ($router) {
    $router->get('/settings/api/webhooks', 'Mpociot\CaptainHook\Http\WebhookController@all');
    $router->post('/settings/api/webhook', 'Mpociot\CaptainHook\Http\WebhookController@store');
    $router->put('/settings/api/webhook/{webhook_id}', 'Mpociot\CaptainHook\Http\WebhookController@update');
    $router->delete('/settings/api/webhook/{webhook_id}', 'Mpociot\CaptainHook\Http\WebhookController@destroy');
    $router->get('/settings/api/webhooks/events', 'Mpociot\CaptainHook\Http\WebhookEventsController@all');
});

if (config('captain_hook.uses_api', false)) {
    Route::group(['middleware' => 'auth:api', 'prefix' => 'api'], function ($router) {
        $router->get('webhooks', 'Mpociot\CaptainHook\Http\WebhookController@all');
        $router->post('webhook', 'Mpociot\CaptainHook\Http\WebhookController@store');
        $router->put('webhook/{webhook_id}', 'Mpociot\CaptainHook\Http\WebhookController@update');
        $router->delete('webhook/{webhook_id}', 'Mpociot\CaptainHook\Http\WebhookController@destroy');
    });
}
