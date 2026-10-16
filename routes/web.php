<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name', 'DukanAI'),
        'status' => 'ok',
        'message' => 'DukanAI backend is running.',
    ]);
});
