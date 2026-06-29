<?php

namespace App\Services;

use RuntimeException;

/**
 * Exception thrown when CV upload validation fails.
 *
 * Contains a machine-readable rejection reason and user-friendly message
 * that can be mapped to client-side validation errors.
 */
class CvUploadException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $field = 'cv',
        public readonly string $reason = 'unknown',
    ) {
        parent::__construct($message);
    }
}
