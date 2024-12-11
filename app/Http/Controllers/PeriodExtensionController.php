<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationPeriodExtension;
use App\Models\PeriodExtension;
use Illuminate\Http\Request;

class PeriodExtensionController extends Controller
{
    public function create(ValidationPeriodExtension $request){
        $period_extension = new PeriodExtension();
        $period_extension->initial_date = $request->initial_date;
        $period_extension->end_date = $request->end_date;
        $period_extension->academic_management_period_id = $request->academic_management_period_id;
        $period_extension->save();
        return $period_extension;
    }
    public function find(){
        $period = PeriodExtension::orderBy('id','ASC')->get();
        return $period;
    }
    public function finAndUpdate(ValidationPeriodExtension $request, string $id){
        $period_extension = PeriodExtension::find($id);
        if(!$period_extension)
            return ["message:", "La extension del periodo con el id:" . $id . " no existe."];
        $period_extension->initial_date = $request->initial_date;
        $period_extension->end_date = $request->end_date;
        $period_extension->academic_management_period_id = $request->academic_management_period_id;
        $period_extension->save();
        return $period_extension;
    }
}
