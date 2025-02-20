<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;

use Carbon\Carbon;

use App\Http\Requests\ValidationsUnit;
use App\Models\Unit;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function find()
    {
        // $units = Unit::with("careers")->get();
        $units = Unit::with("careers")->orderBy('id','ASC')->get();        
        return response()->json($units);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create(ValidationsUnit $request)
    {
        $image = $request->file('logo');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images'. DIRECTORY_SEPARATOR .'units' . DIRECTORY_SEPARATOR . $request->name . DIRECTORY_SEPARATOR . 'Logo' . DIRECTORY_SEPARATOR . $imageName);
        $image->move(public_path('images'. DIRECTORY_SEPARATOR .'units'. DIRECTORY_SEPARATOR . $request->name . DIRECTORY_SEPARATOR . 'Logo'), $imageName);

        $unit = new Unit();
        $unit->name = strtolower($request->name);
        $unit->initials =strtoupper($request->initials);
        $unit->logo = $imagePath;
        $unit->type = $request->type;
        $unit->save();
        return $unit;
    }

    /**
     * Buscar y actualizar por id
     */
    public function findAndUpdate(ValidationsUnit $request, string $id)
    {
        $unit = Unit::find($id);
        if (!$unit)
            return ["message:", "La unidad con id:" . $id . " no existe."];
        if($request->name)
            $unit->name = strtolower($request->name);
        if($request->initials)
            $unit->initials = strtoupper($request->initials);
        if($request->type)
            $unit->type = $request->type;
        
        $image = $request->file('logo');
        if(!$image){
            $unit->save();
            return $unit;
        }
            
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/units/' . $imageName);
        $image->move(public_path('images/units'), $imageName);
        $unit->logo = $imagePath;
        
        $unit->save();
        return $unit;

    }

    /**
     * Buscar una unidad por id
     */
    public function findById(string $id)
    {
        // Obtener la unidad que se va a editar
        $result = DB::table('units')->where('id', $id)->get();

        // Retornar una vista con los datos de la unidad
        return response()->json($result);
    }
}
