<?php

namespace App\Imports;

use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {   
        $mensaje_ = []; // Array para almacenar los mensajes

        foreach ($rows as $row) {
            try {
                // Convertir "/" en "-" si es necesario
                $birthdateFormatted = str_replace('/', '-', $row['fecha_de_nacimiento']);

                // Validar que la fecha tenga el formato correcto (d-m-Y)
                if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $birthdateFormatted)) {
                    $mensaje_[] = "Fila {$row['ci']}: Formato de fecha inválido: " . $row['fecha_de_nacimiento'];
                    continue;
                }

                // Parsear fecha a solo números (dmY)
                $birthdateNumbers = Carbon::createFromFormat('d-m-Y', $birthdateFormatted)->format('dmY');

                // Generar contraseña (CI + fecha en formato ddmmyyyy)
                $ciNumbers = preg_replace('/[^0-9]/', '', $row['ci']);
                $generatedPassword = $ciNumbers . $birthdateNumbers;

                // Encriptar la contraseña
                $hashedPassword = Hash::make($generatedPassword);

                // Verificar si el CI ya existe en la base de datos
                $existingStudent = Student::where('ci', $row['ci'])->exists();
                if ($existingStudent) {
                    $mensaje_[] = "El CI {$row['ci']} ya está registrado. Se omitió el registro.";
                    continue; // Si ya existe, saltamos a la siguiente fila
                }

                // Si los campos estan vacios en la columna apellido_paterno y apellido_materno entonces guardar null
                $paternalSurname = $row['apellido_paterno'] ? $row['apellido_paterno'] : null;
                $maternalSurname = $row['apellido_materno'] ? $row['apellido_materno'] : null;
                //dd($paternalSurname, $maternalSurname);
                try {
                    // Crear el estudiante
                    $student = Student::create([
                        'ci' => $row['ci'],
                        'name' => $row['nombre'],
                        'paternal_surname' => $paternalSurname,
                        'maternal_surname' => $maternalSurname,
                        'phone_number' => $row['telefono'],
                        'birthdate' => $birthdateFormatted,
                        'password' => $hashedPassword,
                        'status' => 'inactivo',
                    ]);
                
                    //dd($student);  // Aquí se mostrará si el estudiante fue creado
                
                    // Verificar si se ha creado correctamente
                    if ($student) {
                        $mensaje_[] = "Estudiante con CI {$row['ci']} creado exitosamente.";
                    } else {
                        $mensaje_[] = "No se pudo crear el estudiante con CI {$row['ci']}.";
                    }
                
                } catch (Exception $e) {
                    // Captura cualquier excepción y muestra el mensaje
                    $mensaje_[] = "Error al crear el estudiante con CI {$row['ci']}: " . $e->getMessage();
                }
                //dd($row['ci']);
            } catch (Exception $e) {
                // Guardar el error en el array de mensajes
                $mensaje_[] = "Error al crear estudiante con CI {$row['ci']}: " . $e->getMessage();
            }
        }

        // Retornar los mensajes
        return response()->json([
            'mensaje_' => $mensaje_
        ]);
    }
}
