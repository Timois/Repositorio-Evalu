<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validations\ValidationException;

use Carbon\Carbon;

use App\Http\Requests\ValidationsPeriod;
use Illuminate\Validation\ValidationException as ValidationValidationException;
use App\Models\Period;

class PeriodController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Crea la tabla con todos sus atributos
     */
    public function create(ValidationsPeriod $request)
    {
        //dd("hola");
        $period = new Period();
        $period->period = $request->period;
        $period->level = $request->level;
        $period->save();
        return $period;
    }

    /**
     * Lista todos los periodos
     */
    public function find(Request $request)
    {
        $periods = DB::table('periods')->get();
        return $periods;
    }

    /**
     * Edita la tabla despues de un darle un id
     */
    public function findAndUpdate(ValidationsPeriod $request, string $id)
    {

        $period = Period::find($id);
        if(!$period)
        return ["message:", "El periodo con el id:" . $id . " no existe."];
        $period->period = $request->period;
        $period->level = $request->level;
        $period->save();
        return $period;
    }

    /**
     * Muestra solo una tabla despues de darle su id
     */
    public function findById(Request $request, string $id)
    {
        $period = Period::find($id);
        if (!$period)
            return ["message:", "El periodo con id:" . $id . " no existe."];
        return $period;
    }
}
