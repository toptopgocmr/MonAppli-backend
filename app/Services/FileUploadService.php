<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload un fichier dans le storage cloud
     */
    public function upload(UploadedFile $file, string $folder, string $disk = 'public'): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, $disk);
        return $path;
    }

    /**
     * Upload photo de profil
     */
    public function uploadProfilePhoto(UploadedFile $file, string $type): string
    {
        return $this->upload($file, "photos/{$type}");
    }

    /**
     * Upload document chauffeur
     */
    public function uploadDocument(UploadedFile $file, int $driverId, string $docType): string
    {
        return $this->upload($file, "documents/drivers/{$driverId}/{$docType}");
    }

    /**
     * Supprimer un fichier
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }
        return false;
    }

    /**
     * Obtenir l'URL publique d'un fichier
     */
    public function url(string $path, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($path);
    }
}
