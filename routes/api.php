<?php

use App\Http\Controllers\AcademicManagementController;
use App\Http\Controllers\AcademicManagementPeriodController;
use App\Http\Controllers\AnswerBankController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuthStudentController;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\BackupAnswerTestController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CareerController;
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
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\RolController;
use App\Http\Controllers\StudentAnswersController;
use App\Http\Controllers\StudenTestsController;
use App\Http\Controllers\StudentEvaluationController;
use App\Http\Controllers\UsersController;


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

$authPersona = ['auth:persona'];
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

// Rutas de Usuarios (requieren permisos de administración)
Route::controller(UsersController::class)->prefix('users')->middleware($authPersona)->group(function(){
    Route::get("/list", "index")->middleware('auth:persona', 'permission:ver-usuarios');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-usuarios');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-usuarios');
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-usuarios');
    Route::post("/assignCareer", "assignCareer")->middleware('auth:persona', 'permission:asignar-carreras-a-usuarios');
    Route::post("/deactivate", "deactivate")->middleware('auth:persona', 'permission:eliminar-usuarios');
});

// Rutas de Permisos
Route::controller(PermisionController::class)->prefix('permissions')->middleware($authPersona)->group(function(){
    Route::get("/list", "index")->middleware('auth:persona', 'permission:ver-permisos');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-permisos-por-id');
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-permisos');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-permisos');
    Route::post("/delete/{id}", "remove")->middleware('auth:persona', 'permission:eliminar-permisos');
});

// Rutas de Roles
Route::controller(RolController::class)->prefix('roles')->middleware($authPersona)->group(function(){
    Route::get("/list", "index")->middleware('auth:persona', 'permission:ver-roles');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-roles-por-id');
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-roles');
    Route::post("/edit/{id}", "update")->middleware('auth:persona', 'permission:editar-roles');
    Route::post("/delete/{id}", "remove")->middleware('auth:persona', 'permission:eliminar-roles');
    Route::post("/removePermision", "removePermision")->middleware('auth:persona', 'permission:editar-roles');
});

// Rutas de Carreras
Route::controller(CareerController::class)->prefix('careers')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-unidades-academicas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-carreras');
    Route::get("/listsFacultiesMayor", "findUnitsMayor")->middleware('auth:persona', 'permission:ver-unidades-academicas');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-unidades-academicas');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-unidades-por-id');
    Route::post("/assignManagement", 'assignManagement')->middleware('auth:persona', 'permission:asignar-gestiones');
    Route::get("/findAsign", 'findAssignManagement')->middleware('auth:persona', 'permission:ver-gestiones-asignadas');
    Route::get("/findByAssignId/{id}", 'findByIdAssign')->middleware('auth:persona', 'permission:ver-gestiones-asignadas-por-id');
    Route::post("/editAssign/{id}", 'findAndUpdateAssign')->middleware('auth:persona', 'permission:editar-asignaciones');
    Route::get("/findPeriodByIdAssign/{id}", 'findPeriodByIdAssign')->middleware('auth:persona', 'permission:ver-periodos-asignados');
});

// Rutas de Gestión Académica
Route::controller(AcademicManagementController::class)->prefix('management')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-gestiones');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-gestiones');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-gestiones');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-gestiones-por-id');
});

Route::controller(AcademicManagementPeriodController::class)->prefix('academic_management_period')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:asignar-periodos');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-periodos-asignados');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-periodo-asignado');
    Route::get("/findByIdCareer/{id}", 'findByIdCareer')->middleware('auth:persona', 'permission:ver-periodos-asignados-por-id');
    Route::get("/findPeriodsByCareerManagement/{career_id}/{academic_management_id}", 'findPeriodsByCareerManagement')->middleware('auth:persona', 'permission:ver-periodos-asignados-por-carrera-y-gestion');
});

// Rutas de Periodos
Route::controller(PeriodController::class)->prefix('periods')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-periodos');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-periodos');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-periodos');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-periodos-por-id');
});

// Rutas de Áreas
Route::controller(AreaController::class)->prefix("areas")->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-areas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-areas');
    Route::get("/listByCareer/{career_id}", "findAreasByCareer")->middleware('auth:persona', 'permission:ver-areas-por-id');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-areas');
    Route::get("/listQuestions/{id}", "questionsByArea")->middleware('auth:persona', 'permission:ver-preguntas-por-area');
    Route::get("/cantityQuestions/{id}", "cantityQuestionsByArea");
    Route::post("/unsubscribe/{id}", "destroy");    
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-areas-por-id');
});

// Rutas de Importación de Excel
Route::controller(ExcelImportController::class)->prefix('excel_import')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:importar-excel');
    Route::get("/list/{id}", "find")->middleware('auth:persona', 'permission:ver-importaciones');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-importaciones');
    Route::delete("/delete/{id}", "destroy");
    Route::get("/findAreaByExcel/{id}", "findAreaByExcel");
});

// Rutas de Importación de Excel con Imágenes
Route::controller(ImportExcelImageController::class)->prefix('excel_import_image')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:importar-excel-con-imagenes');
    Route::post("/savezip", "saveimgezip")->middleware('auth:persona', 'permission:importar-excel-con-imagenes');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-importaciones-con-imagenes');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-importaciones-con-imagenes');
});

// Rutas de Banco de Respuestas
Route::controller(AnswerBankController::class)->prefix('bank_answers')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-respuestas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-respuestas');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-respuestas');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-respuestas-por-id');
    Route::post("/unsubscribe", "remove")->middleware('auth:persona', 'permission:dar-baja-respuestas');
    Route::get("/findByIdQuestion/{id}", 'findByIdQuestion')->middleware('auth:persona', 'permission:ver-respuestas-por-pregunta');
});

// Rutas de Banco de Preguntas
Route::controller(QuestionBankController::class)->prefix('bank_questions')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-preguntas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-preguntas');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-preguntas');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-preguntas-por-id');
    Route::post("/unsubscribe", "remove")->middleware('auth:persona', 'permission:dar-baja-preguntas');
});

Route::controller(ImportStudentController::class)->prefix('students')->middleware($authPersona)->group(function(){
    Route::post("/import", "import")->middleware('auth:persona', 'permission:importar-postulantes');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-postulantes');
    Route::get("/find/{id}", "findById")->middleware('auth:persona', 'permission:buscar-importaciones-de-postulantes-porId');
    Route::get("/findByName/{id}", "findByName")->middleware('auth:persona', 'permission:buscar-importaciones-de-postulantes-porId');
});

Route::controller(EvaluationController::class)->prefix('evaluations')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-evaluaciones');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-evaluaciones');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-evaluaciones');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:buscar-evaluaciones-porId');
    Route::get("/findPeriod/{id}", 'findPeriodById')->middleware('auth:persona', 'permission:ver-informacion-del-periodo-asignado');
    Route::get("/findEvaluationsBYCareer/{id}", 'findEvaluationsByCareer');
});


Route::controller(QuestionEvaluationController::class)->prefix('question_evaluations')->middleware($authPersona)->group(function(){
    Route::post("/cantity", "cantidadPreguntas")->middleware('auth:persona', 'permission:asignar-cantidad-preguntas');
    Route::post("/assign", "AssignRandomQuestions")->middleware('auth:persona', 'permission:generar-pruebas-aleatorias');
    Route::get("/list", "disponibles")->middleware('auth:persona', 'permission:ver-preguntas-disponibles');
    Route::get("listAssigned","find")->middleware('auth:persona', 'permission:ver-preguntas-asignadas');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-preguntas-por-id');
}); 

Route::controller(StudenTestsController::class)->prefix('student_tests')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-pruebas');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-evaluaciones');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-pruebas');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-pruebas-por-id');
    Route::post("/assignRandomEvaluation", "assignRandomEvaluation")->middleware('auth:persona', 'permission:asignar-preguntas-evaluaciones');
    Route::get("/findStudentsByEvaluation/{id}", 'getStudentsByEvaluation')->middleware('auth:persona', 'permission:ver-postulantes-por-evaluacion');
    Route::get("/listQuestionsByStudent/{id}", 'getQuestionsWithAnswers')->middleware('auth:persona', 'permission:ver-preguntas-asignadas');
});

Route::controller(ResultsController::class)->prefix('results')->middleware($authPersona)->group(function(){
    Route::post("/save", "create")->middleware('auth:persona', 'permission:crear-resultados');
    Route::get("/list", "find")->middleware('auth:persona', 'permission:ver-resultados');
    Route::post("/edit/{id}", "findAndUpdate")->middleware('auth:persona', 'permission:editar-resultados');
    Route::get("/find/{id}", 'findById')->middleware('auth:persona', 'permission:ver-resultados-por-id');
});
Route::group(['middleware' => ['auth:api']], function () {
    Route::controller(StudentEvaluationController::class)->prefix('student_evaluations')->group(function(){
        Route::get("/list/{ci}", "findEvaluations");
        Route::get("/find/{id}", "findById");
        Route::get("/questions/{id}", "getQuestionsWithAnswers");
    });
    Route::controller(StudentAnswersController::class)->prefix('student_answers')->group(function(){
        Route::post("/save", "store");  
        Route::get("/list/{student_test_id}", "hasAnswered");
        Route::post("/startTest", "startTest");
    });
    
    Route::controller(BackupAnswerTestController::class)->prefix('backup_answers')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::get("/find/{id}", 'findById');
    });
});