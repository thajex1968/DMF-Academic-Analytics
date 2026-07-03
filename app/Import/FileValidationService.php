<?php

declare(strict_types=1);

namespace DMF\Import;

use Dmf\Core\Validation\ValidationResult;

/**
 * Pre-parse file-level checks (FR-003's "Files over 50 MB are rejected
 * before parsing begins", FR-004/FR-005's "determined from the actual
 * uploaded file's content/MIME type, not merely its filename extension").
 *
 * Distinct from FR-006's structural/content validation of parsed *rows*
 * (Task T2.3, not built here) — this only decides whether a file is safe
 * and well-formed enough to hand to a Parser at all.
 */
final class FileValidationService
{
    /** FR-003. */
    private const MAX_SIZE_BYTES = 50 * 1024 * 1024;

    /** @var array<string, string[]> extension => acceptable finfo MIME types, verified against real generated files. */
    private const ACCEPTED_TYPES = [
        'xlsx' => [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
        ],
        'csv' => [
            'text/plain',
            'text/csv',
            'text/x-csv',
            'application/csv',
        ],
    ];

    public function validate(string $filePath, string $originalFilename, int $declaredSizeBytes): ValidationResult
    {
        $result = new ValidationResult();

        if ($declaredSizeBytes > self::MAX_SIZE_BYTES) {
            $result->addError('file', 'File exceeds the 50 MB size limit.');
        }

        if (!is_file($filePath) || !is_readable($filePath)) {
            $result->addError('file', 'Uploaded file could not be found or read.');

            return $result;
        }

        $extension = $this->detectFileType($originalFilename);

        if (!isset(self::ACCEPTED_TYPES[$extension])) {
            $result->addError('file', 'Unsupported file type — only .xlsx and .csv are accepted.');

            return $result;
        }

        $actualMimeType = $this->detectMimeType($filePath);

        if (!in_array($actualMimeType, self::ACCEPTED_TYPES[$extension], true)) {
            $result->addError(
                'file',
                sprintf(
                    'File content does not match its .%s extension (detected: %s).',
                    $extension,
                    $actualMimeType,
                ),
            );
        }

        return $result;
    }

    /** The file type FR-004/FR-005's parser dispatch keys on — from the extension, not a guess. */
    public function detectFileType(string $originalFilename): string
    {
        return strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    }

    private function detectMimeType(string $filePath): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            return 'application/octet-stream';
        }

        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType === false ? 'application/octet-stream' : $mimeType;
    }
}
