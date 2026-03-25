<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\FileUploadService;
use Illuminate\Http\Request;

class DriverDocumentController extends Controller
{
    public function __construct(private FileUploadService $fileUploadService) {}

    public function index(Request $request)
    {
        $driver    = $request->user();
        $documents = Document::where('driver_id', $driver->id)->latest()->get();

        return response()->json(['success' => true, 'data' => $documents]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:id_card_front,id_card_back,license_front,license_back,vehicle_registration,insurance',
            'file' => 'required|file|max:5120',
        ]);

        $driver = $request->user();

        $path = $this->fileUploadService->uploadDocument(
            $request->file('file'),
            $driver->id,
            $request->type
        );

        $document = Document::updateOrCreate(
            ['driver_id' => $driver->id, 'type' => $request->type],
            ['path' => $path, 'status' => 'pending']
        );

        return response()->json([
            'success' => true,
            'message' => 'Document soumis. En attente de validation.',
            'data'    => $document,
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $document = Document::where('id', $id)
                            ->where('driver_id', $request->user()->id)
                            ->firstOrFail();

        return response()->json(['success' => true, 'data' => $document]);
    }

    public function destroy(Request $request, $id)
    {
        $document = Document::where('id', $id)
                            ->where('driver_id', $request->user()->id)
                            ->firstOrFail();

        $document->delete();

        return response()->json(['success' => true, 'message' => 'Document supprimé.']);
    }
}