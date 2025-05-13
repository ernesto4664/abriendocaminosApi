<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
// TODO: descomenta cuando crees el modelo NNA
// use App\Models\NNA;

class NNAController extends Controller
{
    /**
     * Listar todos los NNA
     */
    public function index()
    {
        try {
            // TODO: reemplaza la siguiente línea con NNA::all()
            $nnaList = []; 

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $nnaList,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error en NNAController@index: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al listar NNA',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear un nuevo NNA
     */
    public function store(Request $request)
    {
        // TODO: agrega aquí las reglas de validación necesarias
        // $request->validate([
        //     'campo1' => 'required|string',
        //     // …
        // ]);

        try {
            // TODO: crea el NNA con algo como:
            // $nna = NNA::create($request->only(['campo1', 'campo2', …]));

            $nna = null; // placeholder

            return response()->json([
                'code' => Response::HTTP_CREATED,
                'data' => $nna,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('Error en NNAController@store: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al crear NNA',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar un NNA por ID
     */
    public function show($id)
    {
        try {
            // TODO: $nna = NNA::findOrFail($id);
            $nna = null; // placeholder

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $nna,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'NNA no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en NNAController@show id={$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener NNA',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar un NNA existente
     */
    public function update(Request $request, $id)
    {
        // TODO: agrega aquí las reglas de validación necesarias
        // $request->validate([...]);

        try {
            // TODO: encuentra y actualiza
            // $nna = NNA::findOrFail($id);
            // $nna->update($request->only(['campo1', 'campo2', …]));

            $nna = null; // placeholder

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $nna,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'NNA no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en NNAController@update id={$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al actualizar NNA',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar un NNA
     */
    public function destroy($id)
    {
        try {
            // TODO: NNA::destroy($id);
            
            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'NNA eliminado correctamente',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'NNA no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en NNAController@destroy id={$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al eliminar NNA',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
