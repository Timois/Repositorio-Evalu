<?php

namespace App\Http\Controllers;

use App\Models\Permision;
use Illuminate\Http\Request;

class PermisionController extends Controller
{
    public function index()
    {
        $permisions = Permision::orderBy('id', 'asc')->get();
        return response()->json($permisions);
    }

    public function find($id){
        $permision = Permision::find($id);
        return response()->json($permision);
    }

    public function create(Request $request){
        $validate = $request->validate([
            'name' => 'required|string|max:50',
        ]);
        $permision = new Permision();
        $permision->name = strtolower($validate['name']);
        $permision->guard_name = 'persona';
        $permision->save();
        return $permision;
    }

    public function findAndUpdate(Request $request, $id){

        $validate = $request->validate([
            'name' => 'required|string|max:50',
        ]);
        $permision = Permision::find($id);
        if (!$permision)
            return ["message:", "El permiso con el id:" . $id . " no existe."];
        $permision->name = strtolower($validate['name']);
        $permision->guard_name = 'persona';
        $permision->save();
        return $permision;
    }

    public function remove($id){
        $permision = Permision::find($id);
        $permision->delete();
        return ["message:", "Permiso eliminado exitosamente"];
    }
}
