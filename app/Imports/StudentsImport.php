<?php

namespace App\Imports;

use App\Models\Evaluation;
use App\Models\Student;
use App\Models\StudentTest;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

class StudentsImport implements ToCollection, WithHeadingRow
{
    protected $requiredColumns = ['ci', 'nombre', 'apellido_paterno', 'apellido_materno', 'fecha_de_nacimiento', 'telefono'];
    protected $results = [];
    protected $evaluationId; 
    public function __construct($evaluationId)
    {
        $this->evaluationId = $evaluationId;

        // Validar que el examen exista
        if (!Evaluation::find($evaluationId)) {
            throw new Exception("El examen con ID {$evaluationId} no existe.");
        }
    }

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

        $currentRow = 1;

        foreach ($rows as $row) {
            $currentRow++;
            $rowResult = [
                'fila' => $currentRow,
                'ci' => $row['ci'],
                'estado' => 'error',
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

                $paternalSurname = trim($row['apellido_paterno'] ?? '');
                $maternalSurname = trim($row['apellido_materno'] ?? '');
                $name = strtolower(trim($row['nombre']));
                $paternalSurname = strtolower($paternalSurname);
                $maternalSurname = strtolower($maternalSurname);
                if (empty($paternalSurname) && empty($maternalSurname)) {
                    $rowResult['mensajes'][] = "Debe proporcionar al menos un apellido (paterno o materno)";
                    $this->results[] = $rowResult;
                    continue;
                }

                $birthdateFormatted = str_replace('/', '-', $row['fecha_de_nacimiento']);
                if (!preg_match('/^\d{2}-\d{2}-\d{4}$/', $birthdateFormatted)) {
                    $rowResult['mensajes'][] = "Formato de fecha inválido: " . $row['fecha_de_nacimiento'];
                    $this->results[] = $rowResult;
                    continue;
                }

                DB::transaction(function () use ($row, $birthdateFormatted, &$rowResult) {
                    // Verificar si el estudiante ya existe
                    $existingStudent = Student::where('ci', $row['ci'])->first();

                    if ($existingStudent) {

                        // Verificar si ya está asociado a un examen
                        if ($existingStudent->evaluations()->exists()) {
                            $rowResult['mensajes'][] = "El estudiante ya está asociado a un examen";
                            $rowResult['estado'] = 'error';
                            return;
                        }

                        // Asociar al examen
                        StudentTest::create([
                            'student_id' => $existingStudent->id,
                            'evaluation_id' => $this->evaluationId,
                            'code' => $row['code'] ?? null,
                            'start_time' => $row['start_time'] ? Carbon::parse($row['start_time'])->format('Y-m-d H:i:s') : null,
                            'end_time' => $row['end_time'] ? Carbon::parse($row['end_time'])->format('Y-m-d H:i:s') : null,
                            'correct_answers' => $row['correct_answers'] ?? null,
                            'incorrect_answers' => $row['incorrect_answers'] ?? null,
                            'not_answered' => $row['not_answered'] ?? null,
                            'score_obtained' => $row['score_obtained'] ?? null,
                            'status' => $row['status'] ?? 'evaluado',
                            'questions_order' => $row['questions_order'] ? json_decode($row['questions_order'], true) : null,
                        ]);

                        $rowResult['estado'] = 'éxito';
                        $rowResult['mensajes'][] = "Estudiante existente asignado al periodo y al examen";
                        return;
                    }
                    
                    $name = strtolower($row['nombre']);
                    $paternalSurname = strtolower($row['apellido_paterno'] ?? '');
                    $maternalSurname = strtolower($row['apellido_materno'] ?? '');
                    if (empty($paternalSurname) && empty($maternalSurname)) {
                        $rowResult['mensajes'][] = "Debe proporcionar al menos un apellido (paterno o materno)";
                        $this->results[] = $rowResult;
                        return;
                    }
                    // Procesar fecha y contraseña
                    $birthdateNumbers = Carbon::createFromFormat('d-m-Y', $birthdateFormatted)->format('dmY');
                    $ciNumbers = preg_replace('/[^0-9]/', '', $row['ci']);
                    $generatedPassword = $ciNumbers . $birthdateNumbers;
                    $hashedPassword = Hash::make($generatedPassword);


                    // Crear estudiante
                    $student = Student::create([
                        'ci' => $row['ci'],
                        'name' => $name,
                        'paternal_surname' => $paternalSurname ?: null,
                        'maternal_surname' => $maternalSurname ?: null,
                        'phone_number' => trim($row['telefono']),
                        'birthdate' => $birthdateFormatted,
                        'password' => $hashedPassword,
                        'status' => 'inactivo',
                    ]);
                    
                    // Asociar al examen
                    StudentTest::create([
                        'student_id' => $student->id,
                        'evaluation_id' => $this->evaluationId,
                        'start_time' => null, // O valor desde el Excel
                        'end_time' => null,
                        'correct_answers' => null,
                        'incorrect_answers' => null,
                        'not_answered' => null,
                        'score_obtained' => null,
                        'status' => 'evaluado', // O valor por defecto
                        'questions_order' => null, // O valor desde el Excel
                    ]);

                    $rowResult['estado'] = 'éxito';
                    $rowResult['mensajes'][] = "Registro creado, asignado al periodo y al examen";
                });
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
