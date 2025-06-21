<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel Async Queues - Docker Queue Balance',
        'info' => 'Use: php artisan process:csv-data --batch-size=10'
    ]);
});
