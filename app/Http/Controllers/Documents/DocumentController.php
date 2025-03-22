<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\CreateDocumentRequest;
use App\Http\Requests\Documents\CreateMultipleDocumentsRequest;
use App\Http\Requests\Documents\GetDocumentRequest;
use App\Http\Resources\Response\WithDataResource;
use App\Http\Resources\Response\WithoutDataResource;
use App\Models\Document;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function getFile(GetDocumentRequest $request)
    {
        $validation = $request->validated();

        try {
            $document = Document::where('id', $validation['file_id'])->first();
            if (!$document || !Storage::exists("public/{$document->path}")) {
                return response()->json(new WithoutDataResource(
                    Response::HTTP_NOT_FOUND,
                    'Dokumen Tidak Ditemukan',
                    'File dengan ID yang diberikan tidak tersedia.'
                ), Response::HTTP_NOT_FOUND);
            }

            return response()->file(storage_path("app/public/{$document->path}"));
        } catch (\Exception $e) {
            Log::error('Error fetching document: ' . $e->getMessage());
            return response()->json(new WithoutDataResource(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Server Error',
                'Terjadi kesalahan saat mengambil dokumen.'
            ), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function uploadMultipleFiles(CreateMultipleDocumentsRequest $request)
    {
        $validation = $request->validated();

        try {
            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                $filename = Str::random(35);
                $mimeType = $file->getClientMimeType();
                $size = $this->getFileSize($file);
                $fileId = Str::uuid()->toString();

                // Simpan file (tanpa ekstensi)
                $filePath = "documents/{$filename}";
                Storage::put("public/{$filePath}", file_get_contents($file));

                $document = Document::create([
                    'id' => $fileId,
                    'user_id' => auth()->id(),
                    'filename' => $filename,
                    'path' => $filePath,
                    'mime_type' => $mimeType,
                    'size' => $size,
                ]);

                // Simpan hasil upload ke array
                $uploadedFiles[] = [
                    'file_id' => $document->id,
                    'filename' => $document->filename,
                    'url' => Storage::url("public/{$filePath}"),
                    'mime_type' => $document->mime_type,
                    'size' => $this->formatFileSize($document->size),
                ];
            }

            return response()->json(new WithDataResource(
                Response::HTTP_CREATED,
                'File berhasil diunggah.',
                'Semua file berhasil diunggah ke server.',
                $uploadedFiles
            ), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error uploading multiple documents: ' . $e->getMessage());
            return response()->json(new WithoutDataResource(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Server Error',
                'Terjadi kesalahan saat mengunggah dokumen. ' . $e->getMessage()
            ), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteFile(GetDocumentRequest $request)
    {
        $request->validated();

        try {
            $deleted = [];

            foreach ($request->file_id as $fileId) {
                $document = Document::where('id', $fileId)->first();

                if ($document && Storage::exists("public/{$document->path}")) {
                    Storage::delete("public/{$document->path}");
                    $document->delete();
                    $deleted[] = $fileId;
                } else {
                    Log::warning("Dokumen tidak ditemukan atau tidak ada di storage: {$fileId}");
                }
            }

            return response()->json(new WithDataResource(
                Response::HTTP_OK,
                'Dokumen Berhasil Dihapus',
                'Dokumen berhasil dihapus.',
                $deleted
            ), Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error deleting document: ' . $e->getMessage());
            return response()->json(new WithoutDataResource(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Server Error',
                'Terjadi kesalahan saat menghapus dokumen.'
            ), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getFileSize($file)
    {
        return $file->getSize(); // Mengembalikan ukuran dalam byte
    }

    private function formatFileSize($bytes)
    {
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($bytes, 1024));

        return sprintf("%.2f", $bytes / pow(1024, $factor)) . ' ' . $sizes[$factor];
    }
}
