<?php

declare(strict_types=1);

namespace DMF\Import\Audit;

/**
 * The structured outcome of one DuplicateDetectionService::detect() run
 * (FR-007) — always returned, never null/void, so a caller can never
 * silently skip checking for duplicates. Four independent signals, any
 * combination of which may be present simultaneously:
 *
 * - $withinFileDuplicates: the same student_id appears more than once in
 *   the file currently being imported.
 * - $alreadyImportedDuplicates: a student_id in this file already has a
 *   committed score for the same assessment, from an earlier import.
 * - $duplicateImportJob: another `import_jobs` row already exists for the
 *   exact same (school, assessment, file_path) — see this class's own
 *   caveat below on why this rarely fires in practice.
 * - $activeDuplicateJobIds: another job for the same school+assessment is
 *   still `queued`/`processing` — a race, not (yet) a committed duplicate.
 */
final class DuplicateCheckResult
{
    /**
     * @param array<string, int[]> $withinFileDuplicates student_id => file row numbers
     *     (every occurrence, including the first) of a student_id that appears more than once.
     * @param array<int, string> $alreadyImportedDuplicates file row number => student_id, for
     *     every row whose student already has a committed score for this assessment.
     * @param array<string, mixed>|null $duplicateImportJob the colliding `import_jobs` row, or null.
     * @param int[] $activeDuplicateJobIds ids of other `queued`/`processing` jobs for the same
     *     school+assessment.
     */
    public function __construct(
        public readonly array $withinFileDuplicates,
        public readonly array $alreadyImportedDuplicates,
        public readonly ?array $duplicateImportJob,
        public readonly array $activeDuplicateJobIds,
        public readonly string $contentHash,
    ) {
    }

    public function hasDuplicates(): bool
    {
        return $this->withinFileDuplicates !== []
            || $this->alreadyImportedDuplicates !== []
            || $this->duplicateImportJob !== null
            || $this->activeDuplicateJobIds !== [];
    }

    /**
     * A safe, human-readable description of every signal present — never includes raw exception
     * detail, SQLSTATE codes, or anything beyond student ids/row numbers/job ids this caller
     * already has legitimate access to. Suitable both for `import_jobs.error_detail` and for the
     * `duplicate_found` audit event's message.
     */
    public function summary(): string
    {
        $parts = [];

        foreach ($this->withinFileDuplicates as $studentId => $rows) {
            $parts[] = sprintf(
                'Duplicate student_id "%s" found within this file (rows %s).',
                $studentId,
                implode(', ', $rows),
            );
        }

        if ($this->alreadyImportedDuplicates !== []) {
            $parts[] = sprintf(
                'Duplicate detected: %d student(s) already have a committed score for this assessment.',
                count($this->alreadyImportedDuplicates),
            );
        }

        if ($this->duplicateImportJob !== null) {
            $parts[] = sprintf(
                'This file was already imported as job #%d.',
                (int) $this->duplicateImportJob['id'],
            );
        }

        if ($this->activeDuplicateJobIds !== []) {
            $parts[] = sprintf(
                'Another import job is already active for this assessment (job id(s): %s).',
                implode(', ', $this->activeDuplicateJobIds),
            );
        }

        return implode(' ', $parts);
    }
}
