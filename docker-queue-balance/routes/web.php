<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Laravel Async Queues - Docker Queue Balance',
        'info' => 'Use: php artisan process:csv-data --batch-size=10',
        'telescope' => 'Access monitoring at: /telescope',
        'queues' => [
            'high-priority' => 'Batch size > 50',
            'default' => 'Batch size > 20',
            'low-priority' => 'Batch size <= 20'
        ]
    ]);
});
