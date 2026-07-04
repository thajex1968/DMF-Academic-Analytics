<?php

declare(strict_types=1);

namespace DMF\Analytics\Aggregation;

use DateTimeImmutable;
use DMF\Analytics\Cache\DashboardCacheInterface;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Context\AnalyticsContextFactory;
use DMF\Analytics\Contracts\AnalyticsResultInterface;
use DMF\Analytics\Dashboard\DashboardAlert;
use DMF\Analytics\Dashboard\DashboardAlertLevel;
use DMF\Analytics\Dashboard\DashboardCard;
use DMF\Analytics\Dashboard\DashboardDataset;
use DMF\Analytics\Dashboard\DashboardMetadata;
use DMF\Analytics\Dashboard\DashboardResponse;
use DMF\Analytics\Dashboard\DashboardSubject;
use DMF\Analytics\Dashboard\DashboardSummary;
use DMF\Analytics\Normalization\ItemIndicatorNormalizer;
use DMF\Analytics\Pipeline\AnalyticsPipeline;
use DMF\Analytics\Result\BenchmarkResult;
use DMF\Analytics\Result\StandardResult;
use DMF\Analytics\Result\StrandResult;
use DMF\Analytics\Result\SubjectResult;
use DMF\Repository\AnalyticsReadRepository;
use DMF\Repository\AssessmentRepository;

/**
 * Merges calculator outputs into a unified Dashboard model (pure,
 * `aggregate()`) and, as the one class every Dashboard Action calls,
 * orchestrates the real data path behind it (`forLatestAssessment()`) —
 * Repository → Normalization → Canonical Context → Pipeline → aggregate.
 * See decisions/IDR-011 §3 for why this is one class, not a fifth,
 * unrequested layer. Never inspects assessment type, source, provider, or
 * report format — only Canonical/calculator-produced data.
 */
final class AnalyticsAggregationService
{
    private const CACHE_TTL_SECONDS = 300;

    public function __construct(
        private readonly AssessmentRepository $assessments,
        private readonly AnalyticsReadRepository $analyticsRead,
        private readonly ItemIndicatorNormalizer $normalizer,
        private readonly AnalyticsContextFactory $contextFactory,
        private readonly AnalyticsPipeline $pipeline,
        private readonly AssessmentSummaryAggregator $assessmentAggregator,
        private readonly SubjectSummaryAggregator $subjectAggregator,
        private readonly StrandSummaryAggregator $strandAggregator,
        private readonly StandardSummaryAggregator $standardAggregator,
        private readonly BenchmarkAggregator $benchmarkAggregator,
        private readonly ?DashboardCacheInterface $cache = null,
    ) {
    }

    /**
     * The real, database-backed entry point every Dashboard Action calls.
     * Returns `null` only when no assessment has been registered yet (a
     * fresh install) — never an exception for that expected state.
     */
    public function forLatestAssessment(): ?DashboardResponse
    {
        $assessmentRow = $this->assessments->findLatest();

        if ($assessmentRow === null) {
            return null;
        }

        $assessmentId = (int) $assessmentRow['id'];
        $cacheKey = sprintf('dashboard:assessment:%d', $assessmentId);

        $cached = $this->cache?->get($cacheKey);

        if ($cached instanceof DashboardResponse) {
            return $cached;
        }

        $metadata = new AnalyticsMetadata(
            $assessmentId,
            (string) $assessmentRow['subject_code'],
            (int) $assessmentRow['academic_year'],
            (int) $assessmentRow['grade_level'],
            new DateTimeImmutable(),
        );

        $responses = $this->analyticsRead->findResponsesForAssessment($assessmentId);
        $normalizationResult = $this->normalizer->normalize($responses);
        $context = $this->contextFactory->fromNormalizationResult($normalizationResult, $metadata);
        $results = $this->pipeline->run($context);

        $response = $this->aggregate($context, $results);

        $this->cache?->set($cacheKey, $response, self::CACHE_TTL_SECONDS);

        return $response;
    }

    /**
     * The pure merge Module 1 describes — no I/O, no cache, always
     * recomputes from exactly what it is given.
     *
     * @param AnalyticsResultInterface[] $results
     */
    public function aggregate(AnalyticsContext $context, array $results): DashboardResponse
    {
        $resultsByCalculator = [];
        foreach ($results as $result) {
            $resultsByCalculator[$result->calculatorName()] = $result;
        }

        /** @var SubjectResult[] $subjectResults */
        $subjectResults = isset($resultsByCalculator['subject-performance'])
            ? $resultsByCalculator['subject-performance']->records()
            : [];
        /** @var StrandResult[] $strandResults */
        $strandResults = isset($resultsByCalculator['strand-performance'])
            ? $resultsByCalculator['strand-performance']->records()
            : [];
        /** @var StandardResult[] $standardResults */
        $standardResults = isset($resultsByCalculator['standard-performance'])
            ? $resultsByCalculator['standard-performance']->records()
            : [];
        /** @var BenchmarkResult[] $benchmarkResults */
        $benchmarkResults = isset($resultsByCalculator['benchmark'])
            ? $resultsByCalculator['benchmark']->records()
            : [];

        $assessment = $this->assessmentAggregator->aggregate($context);
        $subjects = $this->subjectAggregator->aggregate($subjectResults);
        $strands = $this->strandAggregator->aggregate($strandResults);
        $standards = $this->standardAggregator->aggregate($standardResults);
        $benchmarks = $this->benchmarkAggregator->aggregate($benchmarkResults);

        $warnings = [];
        foreach ($results as $result) {
            foreach ($result->warnings() as $warning) {
                $warnings[] = new DashboardAlert(DashboardAlertLevel::WARNING, $warning->identifier, $warning->message);
            }
        }

        return new DashboardResponse(
            new DashboardMetadata(
                $context->metadata->assessmentId,
                $context->metadata->subjectCode,
                $context->metadata->academicYear,
                $context->metadata->gradeLevel,
                $context->metadata->generatedAt,
            ),
            $this->buildSummary(
                $assessment->percentCorrect,
                $assessment->studentCount,
                $assessment->responseCount,
                $subjects,
            ),
            [$assessment],
            $subjects,
            $standards,
            $strands,
            $benchmarks,
            $warnings,
            new DateTimeImmutable(),
        );
    }

    /** @param DashboardSubject[] $subjects */
    private function buildSummary(
        ?float $overallPercentCorrect,
        int $totalStudents,
        int $totalResponses,
        array $subjects,
    ): DashboardSummary {
        $cards = [
            new DashboardCard('Total Students', (float) $totalStudents, null),
            new DashboardCard('Total Responses', (float) $totalResponses, null),
            new DashboardCard('Overall Percent Correct', $overallPercentCorrect, '%'),
        ];

        $datasets = [];

        if ($subjects !== []) {
            $points = [];
            foreach ($subjects as $subject) {
                $points[$subject->subjectCode] = $subject->percentCorrect;
            }

            $datasets[] = new DashboardDataset('Subject Percent Correct', $points);
        }

        return new DashboardSummary($overallPercentCorrect, $totalStudents, $totalResponses, $cards, $datasets);
    }
}
