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

Route::get('/personajes', [PokemonController::class, 'index'])->name('personajes');
Route::post('/guardarFavorito', [PokemonController::class, 'guardarFavorito'])->name('guardarFavorito');
Route::post('/buscar', [PokemonController::class, 'buscar'])->name('buscar');
Route::delete('/eliminarFavorito/{id}', [PokemonController::class, 'eliminarFavorito'])->name('eliminarFavorito');




Route::group(['middleware' => 'auth:sanctum'], function () {
   // Route::post('/logout', [LogoutController::class, 'store']);
});


Route::get('/index/{id}', [LoginController::class, 'index'])->name('index');

Route::get('/registro', [LoginController::class, 'registro']);
Route::get('/iniciarSesion', [LoginController::class, 'iniciarSesion']);
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::get('mandarSMS/{id}',[LoginController::class, 'mandarSMS'])->name('mandarSMS');
Route::post('/validar-codigo',[LoginController::class, 'validarCodigo'])->name('validar-codigo');

Route::post('/registrar-usuario', [LoginController::class, 'registrarUsuario'])->name('registrar-usuario');
Route::post('/validar-correo', [LoginController::class, 'validarCorreo'])->name('validar-correo');

Route::get('/prueba', [LoginController::class, 'prueba']);
Route::post('/log2', [LoginController::class, 'prueba'])->name('log2');
Route::get('/lo', [LoginController::class, 'lo']);

