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
use Illuminate\Support\Facades\Auth;
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
Route::controller(AuthUserController::class)->prefix('users')->group(function(){
    Route::post("/login", "login");
    Route::post("/logout", "logout");
    Route::get("/profile", "me");
    Route::post("/refresh", "refresh");
});

Route::controller(AuthStudentController::class)->prefix('students')->group(function(){
    Route::post("/login", "loginStudent");
    Route::post("/logout", "logoutStudent");
    Route::post("/refresh", "refresh");
    Route::get("/profile", "me");
});
Route::controller(PeriodExtensionController::class)->prefix('period_extension')->group(function(){
    Route::post("/save","create");
    Route::get("/list", "find");
    Route::post("/edit/{id}", "findAndUpdate");
});
Route::controller(ManagementExtensionController::class)->prefix('management_extension')->group(function(){
    Route::post("/save","create");
    Route::get("/list", "find");
    Route::post("/edit/{id}", "findAndUpdate");
});
Route::group(['middleware' => ['auth:persona','role:admin']], function () { 
    Route::controller(UsersController::class)->prefix('users')->group(function(){
        Route::get("/list", "index");
        Route::get("/find/{id}",'findById');
        Route::post("/edit/{id}", "findAndUpdate");
        Route::post("/save", "create");
        Route::post("/assignCareer", "assignCareer");   
        Route::post("/deactivate", "deactivate");
    });
    Route::controller(PermisionController::class)->prefix('permissions')->group(function(){
        Route::get("/list", "index");
        Route::get("/find/{id}",'findById');
        Route::post("/save", "create");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::post("/delete/{id}", "remove");
    });
    
    Route::controller(RolController::class)->prefix('roles')->group(function(){
        Route::get("/list", "index");
        Route::get("/find/{id}",'findById');
        Route::post("/save", "create");
        Route::post("/edit/{id}", "update");
        Route::post("/delete/{id}", "remove");
        Route::post("/removePermision", "removePermision");
    });
    Route::controller(CareerController::class)->prefix('career')->group(function(){
        Route::post("/save","create");
        Route::get("/list", "find");
        Route::get("/listsFacultiesMayor", "findUnitsMayor");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::post("/assignManagement",'assignManagement');
        Route::get("/findAsign", 'findAssignManagement');
        Route::get("/findByAssignId/{id}", 'findByIdAssign');
        Route::post("/saveAssign", 'createAssign');
        Route::post("/editAssign/{id}", 'findAndUpdateAssign');
        Route::get("/findPeriodByIdAssign/{id}", 'findPeriodByIdAssign');
    });
    
    Route::controller(AcademicManagementController::class)->prefix('management')->group(function(){
        Route::post("/save","create")->middleware('permision:crear-gestiones');
        Route::get("/list", "find")->middleware('permision:ver-gestiones');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-gestiones');
        Route::get("/find/{id}",'findById')->middleware('permision:buscar-gestiones-porId');
    });
    
    
    Route::controller(PeriodController::class)->prefix('periods')->group(function(){
        Route::post("/save","create")->middleware('permision:crear-periodos');
        Route::get("/list", "find")->middleware('permision:ver-periodos');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-periodos');
        Route::get("/find/{id}",'findById')->middleware('permision:buscar-periodos-porId');
    });
    Route::controller(AcademicManagementPeriodController::class)->prefix('academic_management_period')->group(function(){
        Route::post("/save","create")->middleware('permision:crear-periodos-asignados-gestiones');
        Route::get("/list", "find")->middleware('permision:ver-periodos-asignados');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-periodos-asignados');
        Route::get("/find/{id}",'findById')->middleware('permision:buscar-periodos-asignados-porId');
        Route::get("/findByIdCareer{id}", "findByIdCareer")->middleware('permision:ver-periodos-asignados-por-carrera');
    });
    
    
    Route::controller(AreaController::class)->prefix("areas")->group(function(){
        Route::post("/save", "create")->middleware('permision:crear-areas');
        Route::get("/list", "find")->middleware('permision:ver-areas');
        Route::get("/listByCareer/{career_id}", "findAreasByCareer")->middleware('permision:ver-areas-por-carrera');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-areas');
        Route::get("/listQuestions/{id}", "questionsByArea")->middleware('permision:ver-preguntas-por-area');
    });
    
    Route::controller(ExcelImportController::class)->prefix('excel_import')->group(function(){
        Route::post("/save", "create")->middleware('permision:importar-excel');
        Route::get("/list", "find")->middleware('permision:ver-importaciones');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-importaciones');
    });
    
    Route::controller(ImportExcelImageController::class)->prefix('excel_import_image')->group(function(){
        Route::post("/save", "create")->middleware('permision:importar-excel-con-imagenes');
        Route::post("/savezip", "saveimgezip")->middleware('permision:guardar-excel-con-imagenes');
        Route::get("/list", "find")->middleware('permision:ver-importaciones-con-imagenes');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-importaciones-con-imagenes');
    });
    
    Route::controller(AnswerBankController::class)->prefix('bank_answers')->group(function(){
        Route::post("/save", "create")->middleware('permision:crear-respuestas');
        Route::get("/list", "find")->middleware('permision:ver-respuestas');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-respuestas');
        Route::get("/find/{id}",'findById')->middleware('permision:buscar-respuestas-porId');
        Route::post("/unsubscribe", "remove")->middleware('permision:eliminar-respuestas');
    });
    
    Route::controller(QuestionBankController::class)->prefix('bank_questions')->group(function(){
        Route::post("/save", "create")->middleware('permision:crear-preguntas');
        Route::get("/list", "find")->middleware('permision:ver-preguntas');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-preguntas');
        Route::get("/find/{id}",'findById')->middleware('permision:buscar-preguntas-porId');
        Route::post("/unsubscribe", "remove")->middleware('permision:dar-baja-preguntas');
    });
    
    Route::controller(EvaluationController::class)->prefix('evaluations')->group(function(){
        Route::post("/save", "create")->middleware('permision:crear-evaluaciones');
        Route::get("/list", "find")->middleware('permision:ver-evaluaciones');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-evaluaciones');
        Route::get("/find/{id}",'findById')->middleware('permision:buscar-evaluaciones-porId');
        Route::get("/listAssignedQuestions", "ListAssignedQuestions")->middleware('permision:ver-preguntas-asignadas');
    });
    
    Route::controller(QuestionEvaluationController::class)->prefix('question_evaluation')->group(function(){
        Route::post("/save", "create")->middleware('permision:crear-preguntas-evaluaciones');
        Route::post("asignQuestion", "assignRandomQuestion")->middleware('permision:asignar-preguntas-evaluaciones');
        Route::get("listAssignedQuestions", "listAssignedQuestions")->middleware('permision:ver-preguntas-asignadas');
        Route::post("assignScores", "assignScores")->middleware('permision:asignar-puntajes-evaluaciones');
        Route::get("listAssignedScores", "listAssignedScores")->middleware('permision:ver-puntajes-asignados');
    });
    
    Route::controller(ImportStudentController::class)->prefix('students')->group(function(){
        Route::post("/save", "create")->middleware('permision:importar-postulantes');
        Route::get("/list", "find")->middleware('permision:ver-importaciones-de-postulantes');
        Route::post("/edit/{id}", "findAndUpdate")->middleware('permision:editar-importaciones-de-postulantes');
        Route::get("/find/{id}",'findById')->middleware('permision:buscar-importaciones-de-postulantes-porId');
        Route::post("/import", "import")->middleware('permision:importar-postulantes');
    });
    
 });
 



