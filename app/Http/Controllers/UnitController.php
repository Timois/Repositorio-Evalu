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
        $units = Unit::get();
        return $units;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create(ValidationsUnit $request)
    {
        $image = $request->file('logo');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/units/' . $imageName);
        $image->move(public_path('images/units'), $imageName);

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
        $image = $request->file('logo');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $imagePath = asset('images/units/' . $imageName);
        $image->move(public_path('images/units'), $imageName);

        $unit = Unit::find($id);
        $unit->name = strtolower($request->name);
        $unit->initials = strtoupper($request->initials);
        $unit->logo = $imagePath;
        $unit->type = $request->type;
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
        return $result;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function unsubscribe(string $id)
    {
        $deleted = DB::table('units')->where("id", $id)->update(["status" => "inactivo"]);
        return $deleted;
    }
}
