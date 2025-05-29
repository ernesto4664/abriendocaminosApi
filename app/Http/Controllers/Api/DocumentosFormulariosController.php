<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\Documento_Formularios;
use Symfony\Component\HttpFoundation\Response;


class DocumentosFormulariosController extends Controller
{
     /**
     * Lista todos los documentos.
     */
    public function index()
    {
        try {
            \Log::info('📄 Listando documentos del formulario');

            $documentos = Documento_Formularios::orderBy('created_at', 'desc')->get();

            return response()->json($documentos, Response::HTTP_OK);

        } catch (\Throwable $e) {
            \Log::error('❌ Error al listar documentos del formulario: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al obtener los documentos'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Almacena un nuevo documento.
     */
    public function store(Request $request)
{
    $request->validate([
        'nombre' => 'required|string',
        'formulario_destino' => 'required|string',
        'archivo' => 'required|file|mimes:pdf,doc,docx,zip|max:20480', // 20MB
    ]);

    if ($request->hasFile('archivo')) {
        $file = $request->file('archivo');
        $nombreArchivo = time() . '-' . $file->getClientOriginalName();
        $ruta = $file->storeAs('documentos', $nombreArchivo, 'public');

        $documento = Documento_Formularios::create([
            'nombre' => $request->nombre,
            'formulario_destino' => $request->formulario_destino,
            'ruta_archivo' => Storage::url($ruta)
        ]);

        return response()->json($documento);
    }

    return response()->json(['error' => 'Archivo no recibido'], 400);
}
public function update(Request $request, $id)
{
    Log::info('Petición recibida', $request->all());

    if ($request->hasFile('archivo')) {
        $file = $request->file('archivo');
        $nombreArchivo = time().'_'.$file->getClientOriginalName();

        // Guarda el archivo en storage/app/public/documentos
        $file->storeAs('documentos', $nombreArchivo, 'public');

        // Actualiza el documento en la base de datos
        $documento = Documento_Formularios::find($id);
        if (!$documento) {
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }

        $documento->nombre = $request->input('nombre');
        $documento->formulario_destino = $request->input('formulario_destino');
        $documento->ruta_archivo = 'documentos/' . $nombreArchivo;
        $documento->save();

        return response()->json(['mensaje' => 'Archivo actualizado correctamente', 'documento' => $documento]);
    }

    return response()->json(['error' => 'No se envió el archivo'], 400);
}


   

    /**
     * Descarga un documento.
     */
    public function download($id)
    {
        try {
            $doc = Documento_Formularios::findOrFail($id);
            
            
            $storagePath = '/storage/app/public/' . $doc->ruta_archivo;
            $downloadName = $doc->nombre . '.' . pathinfo($storagePath, PATHINFO_EXTENSION);

            \Log::info('📥 Descargando documento', [
                'id' => $id,
                'archivo' => $downloadName,
                'ruta' => $storagePath,
            ]);

            return Storage::download($storagePath, $downloadName);
        } catch (\Exception $e) {
            \Log::error('❌ Error al descargar el documento', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Elimina un documento.
     */
    public function destroy($id)
    {
        try {
            $doc = Documento_Formularios::findOrFail($id);
            Storage::delete(str_replace('/storage/', 'public/', $doc->ruta_archivo));
            $doc->delete();

            \Log::info('🗑️ Documento eliminado', [
                'id' => $id
            ]);
        } catch (\Exception $e) {
            \Log::error('❌ Error al eliminar documento', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualiza metadatos de un documento.
     */







        public function show($id)
    {
        \Log::info("📄 Mostrando documento ID={$id}");
        $doc = Documento_Formularios::findOrFail($id);
        // devolvemos el modelo directamente como JSON
        return response()->json($doc, Response::HTTP_OK);
    }
    /**
     * Descarga el documento asociado a formulario_destino = 'NNA'
     */
public function downloadNnaDocumento()
{
    // 1) Buscamos el último formulario destino = 'NNA'
    $doc = Documento_Formularios::where('formulario_destino', 'NNA')
            ->orderBy('created_at', 'desc')
            ->firstOrFail();

    // 2) Construimos la ruta absoluta al disco 'public'
    $diskPath   = $doc->ruta_archivo; // p.e. "documentos/foo.pdf"
    $fullPath   = Storage::disk('public')->path($diskPath);
\Log::info("📄 diskPath{$diskPath}");
\Log::info("📄 fullPath{$fullPath}");
    // 3) Nombre con el que queremos que el usuario descargue
    $extension  = pathinfo($fullPath, PATHINFO_EXTENSION);
    $downloadName = "{$doc->nombre}.{$extension}";
    \Log::info("📄 extension{$extension}");
\Log::info("📄 downloadName{$downloadName}");

    // 4) Devolvemos un BinaryFileResponse, apto para descarga
    //return response()->download($fullPath, $downloadName);
        return response()->json([
        'extension' => $diskPath,
        'downloadName' => $downloadName,
    ], Response::HTTP_OK);
}
public function downloadCuidadorDocumento()
{
    // 1) Buscamos el último formulario destino = 'NNA'
    $doc = Documento_Formularios::where('formulario_destino', 'Cuidador/a Principal')
            ->orderBy('created_at', 'desc')
            ->firstOrFail();

    // 2) Construimos la ruta absoluta al disco 'public'
    $diskPath   = $doc->ruta_archivo; // p.e. "documentos/foo.pdf"
    $fullPath   = Storage::disk('public')->path($diskPath);
\Log::info("📄 diskPath{$diskPath}");
\Log::info("📄 fullPath{$fullPath}");
    // 3) Nombre con el que queremos que el usuario descargue
    $extension  = pathinfo($fullPath, PATHINFO_EXTENSION);
    $downloadName = "{$doc->nombre}.{$extension}";
    \Log::info("📄 extension{$extension}");
\Log::info("📄 downloadName{$downloadName}");

    // 4) Devolvemos un BinaryFileResponse, apto para descarga
    //return response()->download($fullPath, $downloadName);
        return response()->json([
        'extension' => $diskPath,
        'downloadName' => $downloadName,
    ], Response::HTTP_OK);
}      
}
