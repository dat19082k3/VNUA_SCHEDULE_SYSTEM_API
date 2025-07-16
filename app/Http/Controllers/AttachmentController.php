<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Attachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class AttachmentController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Attachment::all(), Response::HTTP_OK);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'uploader_id' => 'required|exists:users,id',
        ]);

        $file = $request->file('file');
        $path = $file->store('attachments');

        $attachment = Attachment::create([
            'file_name' => $file->getClientOriginalName(),
            'file_url' => Storage::url($path),
            'file_type' => $file->getClientMimeType(),
            'uploader_id' => $request->input('uploader_id'),
        ]);

        return response()->json($attachment, Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        $attachment = Attachment::findOrFail($id);
        return response()->json($attachment, Response::HTTP_OK);
    }

    public function destroy(int $id): JsonResponse
    {
        $attachment = Attachment::findOrFail($id);
        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted'], Response::HTTP_NO_CONTENT);
    }
}
