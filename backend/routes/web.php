<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['status' => 'ok']);
});

Route::post('/admin/login', function () {
    return redirect('/admin/login')->with('status', 'Login requires JavaScript. Please refresh the page and try again.');
});
