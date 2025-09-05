<?php

use App\Http\Controllers\AuthTokenController;
use App\Http\Controllers\TranslationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;




Route::get('/', function () {
    return redirect('/api/documentation');
});
