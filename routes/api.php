<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/handle', 'App\Http\Controllers\SpecificController@handle');
Route::post('/test_code', 'App\Http\Controllers\SpecificController@test_code');
    
Route::post('/variable', 'App\Http\Controllers\SpecificController@variable');