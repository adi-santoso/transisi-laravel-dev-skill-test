<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Auth::routes();

Route::middleware(['auth'])->group(function(){
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

    // Custom routes (harus di atas resource agar tidak konflik dengan {company} param)
    Route::get('/companies/{company}/logo', [CompanyController::class, 'logo'])
        ->name('companies.logo');
    Route::get('/companies/{company}/export-employees', [CompanyController::class, 'exportEmployees'])
        ->name('companies.export-employees');
    Route::get('/companies-select2', [CompanyController::class, 'select2'])
        ->name('companies.select2');

    // Import employees (di atas resource agar /employees/import tidak ke-match {employee})
    Route::get('/employees/import', [EmployeeController::class, 'importForm'])
        ->name('employees.import.form');
    Route::post('/employees/import', [EmployeeController::class, 'import'])
        ->name('employees.import');

    Route::resource('/companies', CompanyController::class)->except(['show']);
    Route::resource('/employees', EmployeeController::class)->except(['show']);
});


