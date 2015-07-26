<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    abort(403, 'Access denied');
});

Route::get('bounces/process', 'BouncesController@process');
Route::get('complaints/process', 'ComplaintsController@process');

//Route::get('test', 'BouncesController@getAllListsForEmailAddress');

