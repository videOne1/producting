<?php

use App\Http\Controllers\TestResource;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::resource("testResource", TestResource::class);
Route::get("/test", function () {
    echo "ajde";
});