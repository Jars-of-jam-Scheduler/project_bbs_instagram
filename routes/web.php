<?php

use App\Jobs\FetchInstagramPostsJob;
use App\Connectors\InstagramConnector;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

	$error = request()->query('error');
	if($error) {
		throw new Exception(request()->query('error_description'));
	}

	$code = request()->query('code');
	Cache::put('instagram_authorization_code', $code);
	Cache::put('instagram_authorization_code_expiration', now());

    return view('welcome');
});

Route::get('/make_instagram_authorization', function() {
	$connector = InstagramConnector::getInstance();
	$connector->setClientId(config('services.instagram.client_id'));
	
	return $connector->authorize(route('/'));
});

Route::get('/test_job', function() {
	FetchInstagramPostsJob::dispatch('<an instagram account username>');
});