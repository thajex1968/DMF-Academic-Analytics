<?php

declare(strict_types=1);

namespace DMF\Analytics\Normalization;

/**
 * Iterates already-imported student question responses and turns each into
 * a NormalizedRecord carrying its question's full standard mapping — the
 * "Normalization" stage of docs/Business-Flow.md §4. Never computes mastery,
 * never aggregates scores, never modifies the responses it's given; a
 * mapping that cannot be resolved becomes a traced UnresolvedMapping entry,
 * never a fabricated one.
 *
 * Takes rows as a plain array (the caller has already read them from
 * `student_question_responses`, or from wherever they originate) rather than
 * querying the database itself — the same shape ScoreImportService's row
 * loop works against, keeping this class testable without a database.
 */
final class ItemIndicatorNormalizer
{
    public function __construct(
        private readonly StandardMappingService $mappingService,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $responses Each row: student_id, question_id,
     *     selected_choice (nullable), is_correct.
     */
    public function normalize(array $responses): NormalizationResult
    {
        $records = [];
        $unresolvedMappings = [];
        $warnings = [];

        /** @var array<int, NormalizedStandardMapping> $resolvedMappings Cache — one question can
         *     be answered by many students in the same batch; resolve it once. */
        $resolvedMappings = [];
        /** @var array<int, string> $failedMappings Cache of question ids already known unresolvable. */
        $failedMappings = [];

        foreach (array_values($responses) as $index => $response) {
            $rowNumber = $index + 1;
            $questionId = $this->normalizeQuestionId($response['question_id'] ?? null);

            if ($questionId === null) {
                $unresolvedMappings[] = new UnresolvedMapping($rowNumber, 0, 'Missing or invalid question_id.');

                continue;
            }

            if (isset($failedMappings[$questionId])) {
                $unresolvedMappings[] = new UnresolvedMapping($rowNumber, $questionId, $failedMappings[$questionId]);

                continue;
            }

            if (!isset($resolvedMappings[$questionId])) {
                try {
                    $resolvedMappings[$questionId] = $this->mappingService->map($questionId);
                } catch (UnresolvedMappingException $e) {
                    $failedMappings[$questionId] = $e->getMessage();
                    $unresolvedMappings[] = new UnresolvedMapping($rowNumber, $questionId, $e->getMessage());

                    continue;
                }
            }

            $selectedChoice = $response['selected_choice'] ?? null;

            $records[] = new NormalizedRecord(
                (string) ($response['student_id'] ?? ''),
                $resolvedMappings[$questionId],
                $selectedChoice === null ? null : (string) $selectedChoice,
                (bool) ($response['is_correct'] ?? false),
            );
        }

        return NormalizationResult::build($records, $unresolvedMappings, $warnings, count($responses));
    }

    private function normalizeQuestionId(mixed $raw): ?int
    {
        if (is_int($raw) && $raw > 0) {
            return $raw;
        }

        if (is_string($raw) && ctype_digit($raw) && (int) $raw > 0) {
            return (int) $raw;
        }

        return null;
    }
}
