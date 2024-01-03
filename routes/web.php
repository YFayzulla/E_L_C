<?php

use App\Http\Controllers\DoctorController;
use App\Http\Controllers\ClinicsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

//Route::get('/dashboard', function () {
//    return view('template.template');
//})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*                          USER                */
Route::middleware('auth')->group(function () {

    Route::get('dashboard', [Controller::class, 'index'])->name('user');

    // Room

    Route::resource('room' ,RoomController::class);
    Route::resource('doctor' ,DoctorController::class);

    //test

    Route::get('test',[Controller::class,'test']);

    // Clinics

    Route::get('user/clinic/index', [ClinicsController::class, 'index'])->name('clinic.index');
    Route::get('user/clinic/create', [ClinicsController::class, 'create'])->name('clinic.create');
    Route::post('user/clinic/store', [ClinicsController::class, 'store'])->name('clinic.store');
    Route::get('user/clinic/{clinic}/edit', [ClinicsController::class, 'edit'])->name('clinic.edit');
    Route::put('user/clinic/{clinic}', [ClinicsController::class, 'update'])->name('clinic.update');
    Route::delete('user/clinic/{clinic}', [ClinicsController::class, 'destroy'])->name('clinic.delete');
});

require __DIR__.'/auth.php';
