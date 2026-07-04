<?php

declare(strict_types=1);

namespace DMF\Analytics\Dashboard;

/**
 * Converts Dashboard DTOs into plain JSON-ready arrays — the one place
 * that knows the wire shape, so every Dashboard Action (app/Action/Dashboard/*)
 * serializes identically instead of duplicating array-shaping logic five
 * times. Still no HTML, no Chart.js, no formatting beyond `snake_case`
 * field names (docs/Naming-Convention.md §3).
 */
final class DashboardResponseSerializer
{
    /** @return array<string, mixed> */
    public function metadata(DashboardMetadata $metadata): array
    {
        return [
            'assessment_id' => $metadata->assessmentId,
            'subject_code' => $metadata->subjectCode,
            'academic_year' => $metadata->academicYear,
            'grade_level' => $metadata->gradeLevel,
            'generated_at' => $metadata->generatedAt->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function assessment(DashboardAssessment $assessment): array
    {
        return [
            'assessment_id' => $assessment->assessmentId,
            'student_count' => $assessment->studentCount,
            'response_count' => $assessment->responseCount,
            'correct_count' => $assessment->correctCount,
            'percent_correct' => $assessment->percentCorrect,
        ];
    }

    /** @return array<string, mixed> */
    public function subject(DashboardSubject $subject): array
    {
        return [
            'subject_code' => $subject->subjectCode,
            'percent_correct' => $subject->percentCorrect,
            'average' => $subject->average,
            'highest' => $subject->highest,
            'lowest' => $subject->lowest,
            'distribution' => $subject->distribution,
        ];
    }

    /** @return array<string, mixed> */
    public function strand(DashboardStrand $strand): array
    {
        return [
            'strand_id' => $strand->strandId,
            'strand_code' => $strand->strandCode,
            'subject_code' => $strand->subjectCode,
            'percent_correct' => $strand->percentCorrect,
            'student_count' => $strand->studentCount,
            'response_count' => $strand->responseCount,
            'correct_count' => $strand->correctCount,
        ];
    }

    /** @return array<string, mixed> */
    public function standard(DashboardStandard $standard): array
    {
        return [
            'standard_id' => $standard->standardId,
            'standard_code' => $standard->standardCode,
            'percent_correct' => $standard->percentCorrect,
            'mean' => $standard->mean,
            'median' => $standard->median,
            'min' => $standard->min,
            'max' => $standard->max,
            'standard_deviation' => $standard->standardDeviation,
        ];
    }

    /** @return array<string, mixed> */
    public function benchmark(DashboardBenchmark $benchmark): array
    {
        return [
            'scope' => $benchmark->scope->value,
            'subject_code' => $benchmark->subjectCode,
            'school_percent_correct' => $benchmark->schoolPercentCorrect,
            'benchmark_percent_correct' => $benchmark->benchmarkPercentCorrect,
            'difference' => $benchmark->difference,
        ];
    }

    /** @return array<string, mixed> */
    public function alert(DashboardAlert $alert): array
    {
        return [
            'level' => $alert->level->value,
            'identifier' => $alert->identifier,
            'message' => $alert->message,
        ];
    }

    /** @return array<string, mixed> */
    public function card(DashboardCard $card): array
    {
        return [
            'label' => $card->label,
            'value' => $card->value,
            'unit' => $card->unit,
        ];
    }

    /** @return array<string, mixed> */
    public function dataset(DashboardDataset $dataset): array
    {
        return [
            'label' => $dataset->label,
            'points' => $dataset->points,
        ];
    }

    /** @return array<string, mixed> */
    public function summary(DashboardSummary $summary): array
    {
        return [
            'overall_percent_correct' => $summary->overallPercentCorrect,
            'total_students' => $summary->totalStudents,
            'total_responses' => $summary->totalResponses,
            'cards' => array_map($this->card(...), $summary->cards),
            'datasets' => array_map($this->dataset(...), $summary->datasets),
        ];
    }

    /** @return array<string, mixed> */
    public function response(DashboardResponse $response): array
    {
        return [
            'metadata' => $this->metadata($response->metadata),
            'summary' => $this->summary($response->summary),
            'assessments' => array_map($this->assessment(...), $response->assessments),
            'subjects' => array_map($this->subject(...), $response->subjects),
            'standards' => array_map($this->standard(...), $response->standards),
            'strands' => array_map($this->strand(...), $response->strands),
            'benchmarks' => array_map($this->benchmark(...), $response->benchmarks),
            'warnings' => array_map($this->alert(...), $response->warnings),
            'generation_time' => $response->generationTime->format(DATE_ATOM),
        ];
    }

    /** @return array<string, mixed> */
    public function health(DashboardHealth $health): array
    {
        return [
            'import_status' => $health->importStatus,
            'analytics_status' => $health->analyticsStatus,
            'latest_assessment' => $health->latestAssessmentId !== null ? [
                'assessment_id' => $health->latestAssessmentId,
                'subject_code' => $health->latestAssessmentSubjectCode,
                'academic_year' => $health->latestAssessmentAcademicYear,
            ] : null,
            'latest_calculation' => $health->latestCalculation?->format(DATE_ATOM),
            'total_students' => $health->totalStudents,
            'total_assessments' => $health->totalAssessments,
            'warnings' => array_map($this->alert(...), $health->warnings),
        ];
    }
}
