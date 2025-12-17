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
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

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
                /** ================= VALIDACIONES BÁSICAS ================= */

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

                $paternalSurname = strtolower(trim($row['apellido_paterno'] ?? ''));
                $maternalSurname = strtolower(trim($row['apellido_materno'] ?? ''));

                if (empty($paternalSurname) && empty($maternalSurname)) {
                    $rowResult['mensajes'][] = "Debe proporcionar al menos un apellido (paterno o materno)";
                    $this->results[] = $rowResult;
                    continue;
                }

                /** ================= NORMALIZAR FECHA ================= */

                $rawDate = $row['fecha_de_nacimiento'];

                try {
                    if (is_numeric($rawDate)) {
                        // Fecha serial de Excel
                        $birthdateFormatted = Carbon::instance(
                            ExcelDate::excelToDateTimeObject($rawDate)
                        )->format('d-m-Y');
                    } else {
                        $rawDate = trim($rawDate);
                        $rawDate = str_replace('/', '-', $rawDate);

                        $birthdateFormatted = Carbon::createFromFormat('d-m-Y', $rawDate)
                            ->format('d-m-Y');
                    }
                } catch (\Exception $e) {
                    $rowResult['mensajes'][] = "Fecha de nacimiento inválida: {$rawDate}";
                    $this->results[] = $rowResult;
                    continue;
                }

                /** ================= TRANSACCIÓN ================= */

                DB::transaction(function () use ($row, $birthdateFormatted, &$paternalSurname, &$maternalSurname, &$rowResult) {

                    $existingStudent = Student::where('ci', $row['ci'])->first();

                    if ($existingStudent) {

                        if ($existingStudent->evaluations()
                            ->where('evaluation_id', $this->evaluationId)
                            ->exists()
                        ) {
                            $rowResult['mensajes'][] = "El estudiante ya está asociado a este examen";
                            return;
                        }

                        $existingStudent->evaluations()->attach($this->evaluationId, [
                            'status' => 'pendiente',
                            'code' => 'TEMP',
                            'start_time' => null,
                            'end_time' => null,
                            'correct_answers' => 0,
                            'incorrect_answers' => 0,
                            'not_answered' => 0,
                            'score_obtained' => 0,
                            'questions_order' => json_encode([]),
                        ]);

                        $studentTest = StudentTest::where('student_id', $existingStudent->id)
                            ->where('evaluation_id', $this->evaluationId)
                            ->latest()
                            ->first();

                        $evaluation = Evaluation::with([
                            'academicManagementPeriod.academicManagementCareer.career',
                            'academicManagementPeriod.academicManagementCareer.academicManagement',
                            'academicManagementPeriod.period'
                        ])->findOrFail($this->evaluationId);

                        $sigla = $evaluation->academicManagementPeriod->academicManagementCareer->career->initials;
                        $periodName = str_replace(' ', '', $evaluation->academicManagementPeriod->period->level);
                        $gestion = $evaluation->academicManagementPeriod->academicManagementCareer->academicManagement->year;
                        $title = str_replace(' ', '', $evaluation->title);

                        $code = strtoupper("{$title}-{$sigla}-{$periodName}/{$gestion}-{$studentTest->id}");
                        $studentTest->update(['code' => $code]);

                        $rowResult['estado'] = 'éxito';
                        $rowResult['mensajes'][] = "Estudiante existente asignado al examen";
                        return;
                    }

                    /** ================= CREAR ESTUDIANTE ================= */

                    $name = strtolower($row['nombre']);

                    $birthdateNumbers = Carbon::createFromFormat('d-m-Y', $birthdateFormatted)->format('dmY');
                    $ciNumbers = preg_replace('/\D/', '', $row['ci']);
                    $password = Hash::make($ciNumbers . $birthdateNumbers);

                    $phone = trim($row['telefono'] ?? '');

                    $student = Student::create([
                        'ci' => $row['ci'],
                        'name' => $name,
                        'paternal_surname' => $paternalSurname ?: null,
                        'maternal_surname' => $maternalSurname ?: null,
                        'phone_number' => $phone !== '' ? $phone : null,
                        'birthdate' => $birthdateFormatted,
                        'password' => $password,
                        'status' => 'inactivo',
                    ]);

                    $student->evaluations()->attach($this->evaluationId, [
                        'status' => 'pendiente',
                        'code' => 'TEMP',
                        'start_time' => null,
                        'end_time' => null,
                        'correct_answers' => 0,
                        'incorrect_answers' => 0,
                        'not_answered' => 0,
                        'score_obtained' => 0,
                        'questions_order' => json_encode([]),
                    ]);

                    $studentTest = StudentTest::where('student_id', $student->id)
                        ->where('evaluation_id', $this->evaluationId)
                        ->latest()
                        ->first();

                    $evaluation = Evaluation::with([
                        'academicManagementPeriod.academicManagementCareer.career',
                        'academicManagementPeriod.academicManagementCareer.academicManagement',
                        'academicManagementPeriod.period'
                    ])->findOrFail($this->evaluationId);

                    $sigla = $evaluation->academicManagementPeriod->academicManagementCareer->career->initials;
                    $periodName = str_replace(' ', '', $evaluation->academicManagementPeriod->period->level);
                    $gestion = $evaluation->academicManagementPeriod->academicManagementCareer->academicManagement->year;
                    $title = str_replace(' ', '', $evaluation->title);

                    $code = strtoupper("{$title}-{$sigla}-{$periodName}/{$gestion}-{$studentTest->id}");
                    $studentTest->update(['code' => $code]);

                    $rowResult['estado'] = 'éxito';
                    $rowResult['mensajes'][] = "Registro creado y asignado al examen";
                });
            } catch (Exception $e) {
                $rowResult['mensajes'][] = "Error interno: " . $e->getMessage();
            }

            $this->results[] = $rowResult;
        }

        // Actualizar total de estudiantes
        $uniqueCount = StudentTest::where('evaluation_id', $this->evaluationId)
            ->distinct('student_id')
            ->count('student_id');

        Evaluation::where('id', $this->evaluationId)
            ->update(['qualified_students' => $uniqueCount]);
    }


    public function getResults()
    {
        return $this->results;
    }
}
