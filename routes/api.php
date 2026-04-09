<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;



Route::get('/test', function (Request $request) {

    Log::channel('telegram')->critical('log telegram Base api test');
    return Log::info('teste de log');
});
Route::fallback(fn() => response(["message" => 'Página não encontrada'], 404));

