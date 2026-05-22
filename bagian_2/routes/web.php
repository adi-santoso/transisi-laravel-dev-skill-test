<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth'])->group(function(){
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    Route::get('/companies/{company}/logo', [CompanyController::class, 'logo'])
        ->name('companies.logo');
    Route::resource('/companies', CompanyController::class);

});


