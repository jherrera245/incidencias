<?php

use App\Http\Controllers\Api\RetroalimentacionesController;
use App\Http\Controllers\Api\UsersController;
use App\Models\Retroalimentaciones;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\IncidenciasController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//Login
Route::post('/login', [AuthController::class, 'login']);

//Rutas protegidas
Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/profile', function(Request $request) {
        return auth()->user();
    });

    // API route for logout user
    Route::post('/logout', [AuthController::class, 'logout']);

    //rutas para incidencias
    Route::apiResource('/incidencias', IncidenciasController::class); 

    Route::apiResource('/users', UsersController::class); 

    Route::apiResource('/retroalimentaciones', RetroalimentacionesController::class); 

    //lista tipos de incidencias
    Route::get('/tipos-incidencias', function() {
        return IncidenciasController::listaTiposIncidencias();
    });

    Route::post('/notificacion-incidencia', function(Request $request) {
        return IncidenciasController::sendNotification($request);
    });

    Route::post('/notificacion-resolucion', function(Request $request) {
        return RetroalimentacionesController::sendNotification($request);
    });
});
