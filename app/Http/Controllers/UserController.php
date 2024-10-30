<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class UserController extends Controller
{
    public function create(){
        $usuarios = DB::table('users')->get();
        return response()->json($usuarios);
        //return "Esta es mi ruta para crear";
    }

    public function saveuser(Request $request){
        // Obtener la fecha y hora actual
        $fechaActual = Carbon::now();

        // Formatear la fecha
        $fechaFormateada = $fechaActual->format('d-m-Y H:i:s');
        // Datos a insertar
        $data = [
            "name" => "$request->name",
            "email" => $request->email,
            "password" => bcrypt($request->password), // Cifrar la contraseña con bcrypt
            "created_at" => $fechaFormateada,
            "updated_at" => $fechaFormateada,
        ];

        // Insertar los datos en la tabla 'users'
        DB::table('users')->insert($data);

        // Retornar una respuesta, por ejemplo, redireccionar o mostrar un mensaje
        return response()->json(['message' => 'Usuario creado con éxito']);
    }
}
