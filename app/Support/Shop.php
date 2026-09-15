<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Shop
{
    /**
     * Resolve a stored image path to a public URL.
     */
    public static function imageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
