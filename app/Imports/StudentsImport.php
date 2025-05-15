<?php

namespace App\Imports;

use App\Models\Student;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

class StudentsImport implements ToCollection, WithHeadingRow
{
    protected $requiredColumns = ['ci', 'nombre', 'apellido paterno', 'apellido materno', 'fecha de nacimiento', 'telefono'];
    protected $results = [];
    
    public function collection(Collection $rows)
    {   
        set_time_limit(300); 
        // Validar cabeceras
        $headers = $rows->first()->keys()->toArray();
        
        if (count($headers) !== count($this->requiredColumns)) {
            throw new Exception(json_encode([
                'error' => true,
                'message' => "El archivo no puede ser procesado. El número de columnas no coincide con el formato requerido.",
                'expected' => $this->requiredColumns,
                'received' => $headers
            ]));
        }

        $currentRow = 1; // Contador de filas, empezamos en 1 porque la fila 0 son las cabeceras
        
        foreach ($rows as $row) {
            $currentRow++;
            $rowResult = [
                'fila' => $currentRow,
                'ci' => $row['ci'],
                'estado' => 'error', // Por defecto asumimos error, lo cambiaremos a 'éxito' si todo va bien
                'mensajes' => []
            ];

            try {
                // Validaciones de datos
                if (empty(trim($row['ci']))) {
                    $rowResult['mensajes'][] = "El CI es obligatorio";
                    $this->results[] = $rowResult;
                    continue;
                }

                if (empty(trim($row['nombre']))) {
                    $rowResult['mensajes'][] = "El nombre es obligatorio";
                    $this->results[] = $rowResult;
                    continue;
                }

                // Validar apellidos
                $paternalSurname = trim($row['apellido_paterno'] ?? '');
                $maternalSurname = trim($row['apellido_materno'] ?? '');

                if (empty($paternalSurname) && empty($maternalSurname)) {
                    $rowResult['mensajes'][] = "Debe proporcionar al menos un apellido (paterno o materno)";
                    $this->results[] = $rowResult;
                    continue;
                }

                // Validar formato de fecha
                $birthdateFormatted = str_replace('/', '-', $row['fecha_de_nacimiento']);
                if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $birthdateFormatted)) {
                    $rowResult['mensajes'][] = "Formato de fecha inválido: " . $row['fecha_de_nacimiento'];
                    $this->results[] = $rowResult;
                    continue;
                }
                $existingCIs = Student::pluck('ci')->toArray();
                // Verificar CI duplicado
                if (in_array($row['ci'], $existingCIs))  {
                    $rowResult['mensajes'][] = "El CI ya está registrado en la base de datos";
                    $this->results[] = $rowResult;
                    continue;
                }

                // Procesar fecha y contraseña
                $birthdateNumbers = Carbon::createFromFormat('d-m-Y', $birthdateFormatted)->format('dmY');
                $ciNumbers = preg_replace('/[^0-9]/', '', $row['ci']);
                $generatedPassword = $ciNumbers . $birthdateNumbers;
                $hashedPassword = Hash::make($generatedPassword);

                // Crear estudiante
                $student = Student::create([
                    'ci' => $row['ci'],
                    'name' => trim($row['nombre']),
                    'paternal_surname' => $paternalSurname ?: null,
                    'maternal_surname' => $maternalSurname ?: null,
                    'phone_number' => trim($row['telefono']),
                    'birthdate' => $birthdateFormatted,
                    'password' => $hashedPassword,
                    'status' => 'inactivo',
                ]);
                
                // Si llegamos aquí, todo fue exitoso
                $rowResult['estado'] = 'éxito';
                $rowResult['mensajes'][] = "Registro creado exitosamente";
                
            } catch (Exception $e) {
                $rowResult['mensajes'][] = "Error: " . $e->getMessage();
            }

            $this->results[] = $rowResult;
        }
    }

    public function getResults()
    {
        return $this->results;
    }
}
