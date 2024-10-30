<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnitController;
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

Route::post("/unit", [UnitController::class, 'create']);
Route::get("/unit", [UnitController::class, 'showAll']);
Route::get("/unit/{id}", [UnitController::class, 'findById']);
Route::put("/unit/{id}", [UnitController::class, 'update']);
Route::delete("/unit/{id}", [UnitController::class, 'remove']);



