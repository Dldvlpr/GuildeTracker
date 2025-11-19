<?php

namespace App\Security;

final class ReturnToSanitizer
{
    public static function sanitize(?string $candidate): ?string
    {
        if ($candidate === null) {
            return null;
        }

        $trimmed = trim($candidate);
        if ($trimmed === '') {
            return null;
        }

        if (str_starts_with($trimmed, '//')) {
            return null;
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+\-.]*:#', $trimmed) === 1) {
            return null;
        }

        if (!str_starts_with($trimmed, '/')) {
            return null;
        }

        return $trimmed;
    }

    private function __construct()
    {
    }
}
