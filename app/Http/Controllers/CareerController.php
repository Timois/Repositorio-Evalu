<?php

namespace App\Http\Controllers;

use App\Http\Requests\ValidationAssignManagements;
use Illuminate\Http\Request;

use App\Http\Requests\ValidationsCareer;
use App\Models\AcademicManagementCareer;
use App\Models\AcademicManagementPeriod;
use App\Models\Career;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Worksheet\Validations;

class CareerController extends Controller
{

    public function find()
    {
        $careers = Career::orderBy('id', 'ASC')->whereIn('type', ['carrera'])->get();
        return response()->json($careers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function create(ValidationsCareer $request)
    {
        try {
            // Guardar la imagen en el servidor
            $image = $request->file('logo');
            $basePath = public_path('images' . DIRECTORY_SEPARATOR . 'units');

            // Convertir la sigla a mayúsculas
            $initials = strtoupper($request->initials);

            // Si es un tipo dependiente (carrera o dependiente), usar el nombre del padre en la ruta
            if (in_array($request->type, [Career::TYPE_CARRERA, Career::TYPE_DEPENDIENTE])) {
                $parentUnit = Career::findOrFail($request->unit_id);
                $imageDirectory = $basePath . DIRECTORY_SEPARATOR .
                    $parentUnit->initials . DIRECTORY_SEPARATOR .
                    $initials . DIRECTORY_SEPARATOR . 'Logo';
            } else {
                // Para tipos independientes (mayor o facultad)
                $imageDirectory = $basePath . DIRECTORY_SEPARATOR .
                    $initials . DIRECTORY_SEPARATOR . 'Logo';
            }

            // Asegurar que el directorio existe
            if (!file_exists($imageDirectory)) {
                mkdir($imageDirectory, 0777, true);
            }

            // Generar el nombre de la imagen
            $imageName = time() . '.' . $image->getClientOriginalExtension();

            // Mover la imagen con el nuevo nombre
            $image->move($imageDirectory, $imageName);

            // Construir URL de la imagen
            $imageUrl = in_array($request->type, [Career::TYPE_CARRERA, Career::TYPE_DEPENDIENTE])
                ? asset("images/units/{$parentUnit->initials}/{$initials}/Logo/{$imageName}")
                : asset("images/units/{$initials}/Logo/{$imageName}");

            // Crear la carrera
            $career = new Career();
            $career->name = strtolower($request->name);
            $career->initials = $initials;
            $career->logo = $imageUrl;
            $career->type = $request->type;
            // El unit_id será 0 para tipos independientes y el ID del padre para tipos dependientes
            $career->unit_id = in_array($request->type, Career::INDEPENDENT_TYPES) ? 0 : $request->unit_id;
            $career->save();

            return response()->json([
                'success' => true,
                'message' => 'Registro creado exitosamente',
                'data' => $career
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el registro: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function findAndUpdate(ValidationsCareer $request, string $id)
    {
        try {
            $career = Career::findOrFail($id);
            $oldType = $career->type;
            $oldInitials = $career->initials;
            $oldParentId = $career->unit_id;

            // Actualizar campos básicos si se proporcionan
            if ($request->has('name')) {
                $career->name = strtolower($request->name);
            }
            if ($request->has('initials')) {
                $career->initials = strtoupper($request->initials);
            }
            if ($request->has('type')) {
                $career->type = $request->type;
            }
            if ($request->has('unit_id')) {
                $career->unit_id = $request->unit_id;
            }

            // Validar las relaciones según el tipo
            if (in_array($career->type, Career::INDEPENDENT_TYPES)) {
                $career->unit_id = 0;
            } elseif (in_array($career->type, Career::DEPENDENT_TYPES)) {
                if (!$career->unit_id || !Career::where('id', $career->unit_id)
                    ->whereIn('type', Career::INDEPENDENT_TYPES)
                    ->exists()) {
                    throw new \Exception('Una carrera o dependiente debe pertenecer a una facultad o mayor válido.');
                }
            }

            // Obtener la sigla actualizada
            $initials = strtoupper($career->initials);

            // Manejar la actualización de la imagen si se proporciona
            if ($request->hasFile('logo')) {
                $image = $request->file('logo');
                $basePath = public_path('images' . DIRECTORY_SEPARATOR . 'units');

                // Determinar el nuevo directorio según el tipo
                if (in_array($career->type, [Career::TYPE_CARRERA, Career::TYPE_DEPENDIENTE])) {
                    $parentUnit = Career::findOrFail($career->unit_id);
                    $imageDirectory = $basePath . DIRECTORY_SEPARATOR .
                        $parentUnit->initials . DIRECTORY_SEPARATOR .
                        $initials . DIRECTORY_SEPARATOR . 'Logo';
                } else {
                    $imageDirectory = $basePath . DIRECTORY_SEPARATOR .
                        $initials . DIRECTORY_SEPARATOR . 'Logo';
                }

                // Crear nuevo directorio si no existe
                if (!file_exists($imageDirectory)) {
                    mkdir($imageDirectory, 0777, true);
                }

                // Generar y guardar nueva imagen con la sigla
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move($imageDirectory, $imageName);

                // Construir y guardar nueva URL
                $imageUrl = in_array($career->type, [Career::TYPE_CARRERA, Career::TYPE_DEPENDIENTE])
                    ? asset("images/units/{$parentUnit->initials}/{$initials}/Logo/{$imageName}")
                    : asset("images/units/{$initials}/Logo/{$imageName}");

                // Eliminar imagen anterior si existe
                if ($career->logo) {
                    $oldImagePath = public_path(parse_url($career->logo, PHP_URL_PATH));
                    if (file_exists($oldImagePath)) {
                        unlink($oldImagePath);
                    }
                }

                $career->logo = $imageUrl;
            } else if ($request->has('name') || $request->has('type') || $request->has('unit_id')) {
                // Si cambió el nombre, tipo o unidad padre, pero no la imagen, actualizar la ruta de la imagen
                if ($career->logo) {
                    $oldImageName = basename($career->logo);

                    if (in_array($career->type, [Career::TYPE_CARRERA, Career::TYPE_DEPENDIENTE])) {
                        $parentUnit = Career::findOrFail($career->unit_id);
                        $newImageUrl = asset("images/units/{$parentUnit->initials}/{$initials}/Logo/{$oldImageName}");
                    } else {
                        $newImageUrl = asset("images/units/{$initials}/Logo/{$oldImageName}");
                    }

                    // Mover el archivo físicamente si es necesario
                    $oldImagePath = public_path(parse_url($career->logo, PHP_URL_PATH));
                    $newImagePath = public_path(parse_url($newImageUrl, PHP_URL_PATH));

                    if ($oldImagePath !== $newImagePath && file_exists($oldImagePath)) {
                        $newDirectory = dirname($newImagePath);
                        if (!file_exists($newDirectory)) {
                            mkdir($newDirectory, 0777, true);
                        }
                        rename($oldImagePath, $newImagePath);
                    }

                    $career->logo = $newImageUrl;
                }
            }

            $career->save();

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado exitosamente',
                'data' => $career
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "La carrera con id: $id no existe."
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el registro: ' . $e->getMessage()
            ], 500);
        }
    }


    public function findById(string $id)
    {
        $career = Career::find($id);
        if (!$career)
            return ["message:", "La carrera con id:" . $id . " no existe."];
        return response()->json($career);
    }

    public function assignManagement(ValidationAssignManagements $request)
    {
        $academicManagementCareer = new AcademicManagementCareer();
        $academicManagementCareer->career_id = $request->career_id;
        $academicManagementCareer->academic_management_id = $request->academic_management_id;
        $academicManagementCareer->save();
        return ["message:", "Gestion asignado exitosamente"];
    }

    public function findAssignManagement()
    {
        $assign = AcademicManagementCareer::get();
        return response()->json($assign);
    }

    public function findByIdAssign(string $careerId)
    {
        $managements = AcademicManagementCareer::where('career_id', $careerId)
            ->with(['academicManagement' => function ($query) {
                $query->select('id', 'initial_date', 'end_date');
            }])
            ->get();

        if ($managements->isEmpty())
            return response()->json([]);

        $result = $managements->map(function ($management) {
            return [
                'id' => $management->academicManagement->id,
                'name' => $management->career->name,
                'initial_date' => $management->academicManagement->initial_date,
                'end_date' => $management->academicManagement->end_date,
                'academic_management_career_id' => $management->id
            ];
        });

        return response()->json($result);
    }

    public function findAndUpdateAssign(ValidationAssignManagements $request, string $id)
    {
        $update = AcademicManagementCareer::find($id);
        if (!$update)
            return ["message:", "La gestion academica no existe con el id:" . $id];
        $update->academic_management_id = $request->academic_management_id;
        $update->save();
        return $update;
    }


    public function findPeriodByIdAssign(string $academicManagementCareerId)
    {
        $periods = AcademicManagementPeriod::where('academic_management_career_id', $academicManagementCareerId)
            ->with(['period' => function ($query) {
                $query->select('id', 'period');
            }])
            ->get();

        if ($periods->isEmpty())
            return response()->json([]);

        $result = $periods->map(function ($periods) {
            return [
                'id' => $periods->id,
                'period_id' => $periods->period->id,
                'period' => $periods->period->period,
                'initial_date' => $periods->initial_date,
                'end_date' => $periods->end_date
            ];
        });

        return response()->json($result);
    }

    public function findUnitsMayor() {
        $units = Career::orderBy('id', 'ASC')->whereIn('type', ['mayor', 'facultad','dependiente'])->get();
        return response()->json($units);
    }

}
