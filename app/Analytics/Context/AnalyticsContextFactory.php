<?php

declare(strict_types=1);

namespace DMF\Analytics\Context;

use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Canonical\QuestionAnalyticsRecord;
use DMF\Analytics\Canonical\StandardAnalyticsRecord;
use DMF\Analytics\Canonical\StrandAnalyticsRecord;
use DMF\Analytics\Canonical\SubjectAnalyticsRecord;
use DMF\Analytics\Normalization\NormalizationResult;
use DMF\Analytics\Normalization\NormalizedRecord;
use DMF\Analytics\Normalization\ResolvedStandard;
use DMF\Analytics\Normalization\ResolvedStrand;

/**
 * Builds an AnalyticsContext from a NormalizationResult (the Canonical
 * Analytics Model Normalization, T2.5, already produces) plus caller-supplied
 * AnalyticsMetadata. No Assessment Source logic here — this class never
 * inspects assessment type, source name, provider, or report format, only
 * the already-resolved standard/strand chain each NormalizedRecord carries.
 *
 * Pure grouping and tallying — never a percentage, index, or other derived
 * statistic. That is an AnalyticsCalculatorInterface's job, not yet built.
 */
final class AnalyticsContextFactory
{
    public function fromNormalizationResult(NormalizationResult $result, AnalyticsMetadata $metadata): AnalyticsContext
    {
        /** @var array<int, NormalizedRecord[]> $recordsByQuestion */
        $recordsByQuestion = [];
        foreach ($result->records as $record) {
            $recordsByQuestion[$record->mapping->questionId][] = $record;
        }

        $questionRecords = [];
        /** @var array<int, ResolvedStandard> $standardById */
        $standardById = [];
        /** @var array<int, NormalizedRecord[]> $recordsByStandard */
        $recordsByStandard = [];

        foreach ($recordsByQuestion as $questionId => $records) {
            $standard = $records[0]->mapping->primaryIndicator->standard;
            $tally = $this->tally($records);

            $questionRecords[] = new QuestionAnalyticsRecord(
                $questionId,
                $standard->id,
                $tally['studentCount'],
                $tally['responseCount'],
                $tally['correctCount'],
            );

            $standardById[$standard->id] = $standard;
            foreach ($records as $record) {
                $recordsByStandard[$standard->id][] = $record;
            }
        }

        $standardRecords = [];
        /** @var array<int, ResolvedStrand> $strandById */
        $strandById = [];
        /** @var array<int, NormalizedRecord[]> $recordsByStrand */
        $recordsByStrand = [];

        foreach ($recordsByStandard as $standardId => $records) {
            $standard = $standardById[$standardId];
            $tally = $this->tally($records);

            $standardRecords[] = new StandardAnalyticsRecord(
                $standardId,
                $standard->standardCode,
                $standard->strand->id,
                $tally['studentCount'],
                $tally['responseCount'],
                $tally['correctCount'],
            );

            $strandById[$standard->strand->id] = $standard->strand;
            foreach ($records as $record) {
                $recordsByStrand[$standard->strand->id][] = $record;
            }
        }

        $strandRecords = [];
        /** @var array<string, NormalizedRecord[]> $recordsBySubject */
        $recordsBySubject = [];

        foreach ($recordsByStrand as $strandId => $records) {
            $strand = $strandById[$strandId];
            $tally = $this->tally($records);

            $strandRecords[] = new StrandAnalyticsRecord(
                $strandId,
                $strand->strandCode,
                $strand->subjectCode,
                $tally['studentCount'],
                $tally['responseCount'],
                $tally['correctCount'],
            );

            foreach ($records as $record) {
                $recordsBySubject[$strand->subjectCode][] = $record;
            }
        }

        $subjectRecords = [];
        foreach ($recordsBySubject as $subjectCode => $records) {
            $tally = $this->tally($records);

            $subjectRecords[] = new SubjectAnalyticsRecord(
                $subjectCode,
                $tally['studentCount'],
                $tally['responseCount'],
                $tally['correctCount'],
            );
        }

        $overallTally = $this->tally($result->records);

        $assessmentRecord = new AssessmentAnalyticsRecord(
            $metadata->assessmentId,
            $overallTally['studentCount'],
            $overallTally['responseCount'],
            $overallTally['correctCount'],
        );

        return new AnalyticsContext(
            $metadata,
            $assessmentRecord,
            $subjectRecords,
            $strandRecords,
            $standardRecords,
            $questionRecords,
        );
    }

    /**
     * @param NormalizedRecord[] $records
     * @return array{studentCount: int, responseCount: int, correctCount: int}
     */
    private function tally(array $records): array
    {
        $studentIds = [];
        $correctCount = 0;

        foreach ($records as $record) {
            $studentIds[$record->studentId] = true;

            if ($record->isCorrect) {
                $correctCount++;
            }
        }

        return [
            'studentCount' => count($studentIds),
            'responseCount' => count($records),
            'correctCount' => $correctCount,
        ];
    }
}
