<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAcademicManagementPeriod;
use App\Models\AcademicManagementPeriod;
use Illuminate\Http\Request;

class AcademicManagementPeriodController extends Controller
{
    public function find(){
        $academicManagementPeriod = AcademicManagementPeriod::get();
        return $academicManagementPeriod;
    }
    public function create(ValidationAcademicManagementPeriod $request){
        $academicManagementPeriod = new AcademicManagementPeriod();
        $academicManagementPeriod->initial_date = $request->initial_date;
        $academicManagementPeriod->end_date = $request->end_date;
        $academicManagementPeriod->academic_management_career_id = $request->academic_management_career_id;
        $academicManagementPeriod->status = $request->status;
        $academicManagementPeriod->period_id = $request->period_id;
    }
}
