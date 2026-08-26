<?php

namespace App\Domain\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileUploadService
{
    protected const ALLOWED_MIME = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    protected const MAX_SIZE_KB = 5120;

    public function upload(UploadedFile $file, string $directory = 'uploads', ?string $disk = null): string
    {
        $this->validate($file);

        $disk = $disk ?? config('filesystems.default');

        $filename = Str::uuid()->toString().'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, ['disk' => $disk]);

        if (! $path) {
            throw ValidationException::withMessages(['file' => 'File upload failed.']);
        }

        return $path;
    }

    public function delete(?string $path, ?string $disk = null): void
    {
        if (! $path) {
            return;
        }

        $disk = $disk ?? config('filesystems.default');

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);
        }
    }

    public function url(?string $path, ?string $disk = null): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = $disk ?? config('filesystems.default');

        return Storage::disk($disk)->url($path);
    }

    protected function validate(UploadedFile $file): void
    {
        if (! in_array($file->getMimeType(), self::ALLOWED_MIME, true)) {
            throw ValidationException::withMessages([
                'file' => 'File type not allowed. Accepted: jpeg, png, webp, gif, pdf.',
            ]);
        }

        if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
            throw ValidationException::withMessages([
                'file' => 'File size exceeds the maximum of '.self::MAX_SIZE_KB.' KB.',
            ]);
        }
    }
}