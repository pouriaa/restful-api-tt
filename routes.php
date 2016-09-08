<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
*/
Route::get('/', 'ComicController@index');


Route::resource('comic', 'ComicController');
Route::get('comic/{slug}', 'ComicController@show');

Route::get('popular', function(){ return redirect('/popular/3'); });
Route::get('popular/{days}', 'ComicController@popular');

Route::resource('blog', 'BlogController');

Route::get('archive/{season?}', 'ComicController@archive');

Route::get('auth/register', 'ComicController@index');

Route::get('pre-order', 'EcommerceController@preOrder');

Route::controllers([
	'auth' => 'Auth\AuthController',
	'password' => 'Auth\PasswordController',
]);


// In case the user is linked from an old link
Route::get('{id}', 'ComicController@legacy');



