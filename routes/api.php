<?php

use App\Http\Controllers\AcademicManagementController;
use App\Http\Controllers\AcademicManagementPeriodController;
use App\Http\Controllers\AnswerBankController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthStudentController;
use App\Http\Controllers\AuthUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\CareerController;
use App\Http\Controllers\EvaluationAreaScoreController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\ImportExcelImageController;
use App\Http\Controllers\ImportStudentController;
use App\Http\Controllers\ManagementExtensionController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\PeriodExtensionController;
use App\Http\Controllers\PermisionController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\QuestionEvaluationController;
use App\Http\Controllers\ResponsibleController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\StudentsImportController;
use App\Http\Controllers\UsersController;
use App\Models\Permision;
use App\Models\Student;
use Maatwebsite\Excel\Row;
use Symfony\Component\Console\Question\Question;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// Autenticación de usuarios
Route::controller(AuthUserController::class)->prefix('users')->group(function(){
    Route::post("/login", "login");
    Route::post("/logout", "logout");
    Route::get("/refreshPermissions", "refreshPermissions");
    Route::get("/profile", "me");
    Route::post("/refresh", "refresh");
});

// Autenticación de estudiantes
Route::controller(AuthStudentController::class)->prefix('students')->group(function(){
    Route::post("/login", "loginStudent");
    Route::post("/logout", "logoutStudent");
    Route::post("/refresh", "refresh");
    Route::get("/profile", "me");
});

// Extensiones de periodos
Route::controller(PeriodExtensionController::class)->prefix('period_extension')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-periodos');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-periodos');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-periodos');
});

// Extensiones de gestión
Route::controller(ManagementExtensionController::class)->prefix('management_extension')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-gestiones');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-gestiones');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-gestiones');
});

// Rutas de Usuarios (requieren permisos de administración)
Route::controller(UsersController::class)->prefix('users')->group(function(){
    Route::get("/list", "index")->middleware('auth:persona', 'permission:ver-usuarios');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-usuarios');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-usuarios');
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-usuarios');
    Route::post("/assignCareer", "assignCareer")->middleware('auth:persona', 'permission:asignar-gestiones');
    Route::post("/deactivate", "deactivate")->middleware('auth:persona', 'permission:eliminar-usuarios');
});

// Rutas de Permisos
Route::controller(PermisionController::class)->prefix('permissions')->group(function(){
    Route::get("/list", "index")->middleware('auth:persona', 'permission:ver-permisos');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-permisos');
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-permisos');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-permisos');
    Route::post("/delete/{id}", "remove")->middleware('auth:persona', 'permission:eliminar-permisos');
});

// Rutas de Roles
Route::controller(RolController::class)->prefix('roles')->group(function(){
    Route::get("/list", "index")->middleware('auth:persona', 'permission:ver-roles');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-roles-por-id');
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-roles');
    Route::post("/edit/{id}", "update")->middleware('auth:persona', 'permission:editar-roles');
    Route::post("/delete/{id}", "remove")->middleware('auth:persona', 'permission:eliminar-roles');
    Route::post("/removePermision", "removePermision")->middleware('auth:persona', 'permission:editar-roles');
});

// Rutas de Carreras
Route::controller(CareerController::class)->prefix('careers')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-carreras');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-carreras');
    Route::get("/listsFacultiesMayor", "findUnitsMayor")->middleware('auth:persona', 'permission:ver-carreras');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-carreras');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-carreras');
    Route::post("/assignManagement", 'assignManagement')->middleware('auth:persona', 'permission:asignar-gestiones');
    Route::get("/findAsign", 'findAssignManagement')->middleware('auth:persona', 'permission:ver-gestiones-asignadas');
    Route::get("/findByAssignId/{id}", 'findByIdAssign')->middleware('auth:persona', 'permission:ver-gestiones-asignadas');
    Route::post("/saveAssign", 'createAssign')->middleware('auth:persona', 'permission:crear-gestiones');
    Route::post("/editAssign/{id}", 'findAndUpdateAssign')->middleware('auth:persona', 'permission:editar-gestiones');
    Route::get("/findPeriodByIdAssign/{id}", 'findPeriodByIdAssign')->middleware('auth:persona', 'permission:ver-periodos-asignados');
});

// Rutas de Gestión Académica
Route::controller(AcademicManagementController::class)->prefix('management')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-gestiones');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-gestiones');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-gestiones');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-gestiones');
});

// Rutas de Periodos
Route::controller(PeriodController::class)->prefix('periods')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-periodos');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-periodos');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-periodos');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-periodos');
});

// Rutas de Áreas
Route::controller(AreaController::class)->prefix("areas")->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-areas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-areas');
    Route::get("/listByCareer/{career_id}", "findAreasByCareer")->middleware('auth:persona', 'permission:ver-areas');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-areas');
    Route::get("/listQuestions/{id}", "questionsByArea")->middleware('auth:persona', 'permission:ver-preguntas-por-area');
});

// Rutas de Importación de Excel
Route::controller(ExcelImportController::class)->prefix('excel_import')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:importar-excel');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-importaciones');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-importaciones');
});

// Rutas de Importación de Excel con Imágenes
Route::controller(ImportExcelImageController::class)->prefix('excel_import_image')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:importar-excel-con-imagenes');
    Route::post("/savezip", "saveimgezip")->middleware('auth:persona', 'permission:importar-excel-con-imagenes');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-importaciones-con-imagenes');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-importaciones-con-imagenes');
});

// Rutas de Banco de Respuestas
Route::controller(AnswerBankController::class)->prefix('bank_answers')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-respuestas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-respuestas');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-respuestas');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-respuestas');
    Route::post("/unsubscribe", "remove")->middleware('auth:persona', 'permission:dar-baja-respuestas');
});

// Rutas de Banco de Preguntas
Route::controller(QuestionBankController::class)->prefix('bank_questions')->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-preguntas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-preguntas');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-preguntas');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-preguntas');
    Route::post("/unsubscribe", "remove")->middleware('auth:persona', 'permission:dar-baja-preguntas');
});
