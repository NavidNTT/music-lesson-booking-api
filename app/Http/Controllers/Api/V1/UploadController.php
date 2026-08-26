<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Services\FileUploadService;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected FileUploadService $uploadService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file'],
            'directory' => ['nullable', 'string', 'max:100'],
        ]);

        $path = $this->uploadService->upload(
            $request->file('file'),
            $request->input('directory', 'uploads')
        );

        return $this->created(
            data: [
                'path' => $path,
                'url' => $this->uploadService->url($path),
            ],
            message: 'File uploaded successfully.'
        );
    }
}