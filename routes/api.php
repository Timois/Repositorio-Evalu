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
    Route::controller(CareerController::class)->prefix('careers')->group(function(){
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
        Route::post("/save","create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
    });
    
    
    Route::controller(PeriodController::class)->prefix('periods')->group(function(){
        Route::post("/save","create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
    });
    Route::controller(AcademicManagementPeriodController::class)->prefix('academic_management_period')->group(function(){
        Route::post("/save","create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::get("/findByIdCareer{id}", "findByIdCareer");
    });
    
    
    Route::controller(AreaController::class)->prefix("areas")->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::get("/listByCareer/{career_id}", "findAreasByCareer");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/listQuestions/{id}", "questionsByArea");
    });
    
    Route::controller(ExcelImportController::class)->prefix('excel_import')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
    });
    
    Route::controller(ImportExcelImageController::class)->prefix('excel_import_image')->group(function(){
        Route::post("/save", "create");
        Route::post("/savezip", "saveimgezip");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
    });
    
    Route::controller(AnswerBankController::class)->prefix('bank_answers')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::post("/unsubscribe", "remove");
    });
    
    Route::controller(QuestionBankController::class)->prefix('bank_questions')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::post("/unsubscribe", "remove");
    });
    
    Route::controller(EvaluationController::class)->prefix('evaluations')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::get("/listAssignedQuestions", "ListAssignedQuestions");
    });
    
    Route::controller(QuestionEvaluationController::class)->prefix('question_evaluation')->group(function(){
        Route::post("/save", "create")->middleware('permision:crear-preguntas-evaluaciones');
        Route::post("asignQuestion", "assignRandomQuestion");
        Route::get("listAssignedQuestions", "listAssignedQuestions");
        Route::post("assignScores", "assignScores");
        Route::get("listAssignedScores", "listAssignedScores");
    });
    
    Route::controller(ImportStudentController::class)->prefix('students')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::post("/import", "import");
    });
    
 });
 
 Route::group(['middleware' => ['auth:persona', 'role:docente']], function () {
    Route::controller(QuestionBankController::class)->prefix('bank_questions')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::post("/unsubscribe", "remove");
    });

    Route::controller(AnswerBankController::class)->prefix('bank_answers')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::post("/unsubscribe", "remove");
    });

    Route::controller(EvaluationController::class)->prefix('evaluations')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::get("/listAssignedQuestions", "ListAssignedQuestions");
    });

    Route::controller(QuestionEvaluationController::class)->prefix('question_evaluation')->group(function(){
        Route::post("/save", "create")->middleware('permision:crear-preguntas-evaluaciones');
        Route::post("asignQuestion", "assignRandomQuestion");
        Route::get("listAssignedQuestions", "listAssignedQuestions");
        Route::post("assignScores", "assignScores");
        Route::get("listAssignedScores", "listAssignedScores");
    });
    Route::controller(ImportStudentController::class)->prefix('students')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
        Route::get("/find/{id}",'findById');
        Route::post("/import", "import");
    });
    Route::controller(ExcelImportController::class)->prefix('excel_import')->group(function(){
        Route::post("/save", "create");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
    });
    
    Route::controller(ImportExcelImageController::class)->prefix('excel_import_image')->group(function(){
        Route::post("/save", "create");
        Route::post("/savezip", "saveimgezip");
        Route::get("/list", "find");
        Route::post("/edit/{id}", "findAndUpdate");
    });
 });



