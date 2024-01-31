<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PokemonController;
use App\Http\Controllers\LoginController;
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



Route::group(['middleware' => 'auth:sanctum'], function () {
   // Route::post('/logout', [LogoutController::class, 'store']);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/index/{id}', [LoginController::class, 'index'])->name('index');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
});

Route::get('/registro', [LoginController::class, 'registro']);
Route::get('/iniciarSesion', [LoginController::class, 'iniciarSesion'])->name('iniciarSesion');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('mandarSMS/{id}',[LoginController::class, 'mandarSMS'])->name('mandarSMS');
Route::post('/validar-codigo',[LoginController::class, 'validarCodigo'])->name('validar-codigo');

Route::post('/registrar-usuario', [LoginController::class, 'registrarUsuario'])->name('registrar-usuario');


