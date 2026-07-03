<?php

declare(strict_types=1);

namespace DMF\Import\Score;

use DMF\Repository\AssessmentRepository;

/**
 * Confirms an `assessment_id` (already recorded on the import_job at
 * upload time — see UploadService) is a real, known assessment before
 * scores are committed against it.
 */
final class AssessmentResolver
{
    public function __construct(private readonly AssessmentRepository $assessments)
    {
    }

    /** @return array<string, mixed>|null Null if no assessment exists with this id. */
    public function resolve(int $assessmentId): ?array
    {
        return $this->assessments->findById($assessmentId);
    }
}
