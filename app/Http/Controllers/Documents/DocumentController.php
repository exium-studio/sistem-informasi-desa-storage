<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\CreateDocumentRequest;
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
            if (!$document) {
                return response()->json(new WithoutDataResource(
                    Response::HTTP_NOT_FOUND,
                    'Dokumen Tidak Ditemukan',
                    'File dengan ID yang diberikan tidak tersedia.'
                ), Response::HTTP_NOT_FOUND);
            }

            $filePath = public_path($document->path);
            if (!file_exists($filePath)) {
                return response()->json(new WithoutDataResource(
                    Response::HTTP_NOT_FOUND,
                    'Dokumen Tidak Ditemukan',
                    'Dokumen path tidak tersedia di server.'
                ), Response::HTTP_NOT_FOUND);
            }

            return response()->file($filePath);
        } catch (\Exception $e) {
            // Log::error('Error fetching document: ' . $e->getMessage());
            return response()->json(new WithoutDataResource(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Server Error',
                'Terjadi kesalahan saat mengambil dokumen. Error: ' . $e->getMessage()
            ), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function uploadFile(CreateDocumentRequest $request)
    {
        $validation = $request->validated();

        try {
            $file = $request->file('file');
            $filename = Str::random(35);
            $mimeType = $file->getClientMimeType();
            $size = $this->getFileSize($file);

            // Generate UUID untuk dokumen
            $fileId = Str::uuid()->toString();

            // Tentukan path penyimpanan langsung di public/documents
            $destinationPath = public_path('documents');

            // Pastikan folder ada
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }

            // Simpan file di public/documents
            $file->move($destinationPath, $filename);

            // Path yang akan disimpan di database
            $filePath = "documents/{$filename}";

            $document = Document::create([
                'id' => $fileId,
                'user_id' => auth()->id(),
                'filename' => $filename,
                'path' => $filePath,
                'mime_type' => $mimeType,
                'size' => $size,
            ]);

            return response()->json(new WithDataResource(
                Response::HTTP_CREATED,
                'File berhasil diunggah.',
                'File berhasil diunggah kedalam server.',
                [
                    'file_id' => $document->id,
                    'filename' => $document->filename,
                    'url' => asset($document->path),
                    'mime_type' => $document->mime_type,
                    'size' => $this->formatFileSize($document->size),
                ]
            ), Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error uploading document: ' . $e->getMessage());
            return response()->json(new WithoutDataResource(
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'Server Error',
                'Terjadi kesalahan saat mengunggah dokumen.'
            ), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function deleteFile(GetDocumentRequest $request)
    {
        $validation = $request->validated();

        try {
            $document = Document::where('id', $validation['file_id'])->first();
            if (!$document) {
                return response()->json(new WithoutDataResource(
                    Response::HTTP_NOT_FOUND,
                    'Dokumen Tidak Ditemukan',
                    'File dengan ID yang diberikan tidak tersedia.'
                ), Response::HTTP_NOT_FOUND);
            }

            $filePath = public_path($document->path);
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $document->delete();

            return response()->json(new WithoutDataResource(
                Response::HTTP_OK,
                'Dokumen Berhasil Dihapus',
                'Dokumen berhasil dihapus.'
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
