<?php

declare(strict_types=1);

namespace DMF\Import;

use Dmf\Core\Exception\ValidationException;
use Dmf\Core\Security\Sanitizer;

/**
 * Receives a staged upload, validates it, moves it into permanent storage,
 * and registers it as a queued import job.
 *
 * Deliberately does not touch `$_FILES` or call `move_uploaded_file()` —
 * relocating a file out of PHP's upload temp location is the (not-yet-built)
 * HTTP Action layer's job (docs/02-System-Architecture.md §6/§7); this
 * class operates on any regular file path, which keeps it testable without
 * a real HTTP request and keeps the HTTP-specific mechanics out of the
 * Service layer.
 *
 * Duplicate-import detection (FR-007) is deliberately not implemented here
 * — it depends on the *parsed* student set, which does not exist until a
 * Parser runs (Task T2.6, not in this pass's scope). This class only
 * prevents the exact-same-file-path collision the table's own
 * `uq_import_jobs_school_assessment_file` unique key already guards against.
 */
final class UploadService
{
    public function __construct(
        private readonly FileValidationService $validation,
        private readonly ImportJobManager $jobManager,
        private readonly string $storageDirectory,
    ) {
    }

    /**
     * @throws ValidationException If the file fails validation or cannot be staged.
     */
    public function upload(
        string $sourceFilePath,
        string $originalFilename,
        int $declaredSizeBytes,
        int $schoolId,
        int $assessmentId,
        int $uploadedBy,
    ): int {
        $this->validation
            ->validate($sourceFilePath, $originalFilename, $declaredSizeBytes)
            ->throwIfFailed();

        $fileType = $this->validation->detectFileType($originalFilename);
        $destination = $this->stagingPathFor($originalFilename);

        if (!rename($sourceFilePath, $destination)) {
            throw ValidationException::withErrors([
                'file' => ['Uploaded file could not be staged for processing.'],
            ]);
        }

        return $this->jobManager->createQueuedJob([
            'school_id' => $schoolId,
            'assessment_id' => $assessmentId,
            'file_path' => $destination,
            'file_type' => $fileType,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    /**
     * A collision-safe destination under storage/imports/ — never trusts the
     * original filename alone (traversal-stripped via Sanitizer::filename(),
     * and prefixed with a random token so two uploads of the same original
     * filename never collide).
     */
    private function stagingPathFor(string $originalFilename): string
    {
        $safeName = Sanitizer::filename($originalFilename);
        $token = bin2hex(random_bytes(8));

        return rtrim($this->storageDirectory, '/\\') . DIRECTORY_SEPARATOR . $token . '_' . $safeName;
    }
}
