<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Audit;

use DMF\Import\Audit\AuditEvent;
use DMF\Import\Audit\AuditTrailService;
use DMF\Import\Audit\DuplicateCheckResult;
use DMF\Import\Audit\ImportAuditLogger;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AuditTrailServiceTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $state;

    private AuditTrailService $service;

    protected function setUp(): void
    {
        $this->state = ['jobs' => [], 'logs' => []];

        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturnCallback(fn (): string => (string) (count($this->state['logs']) + 1));

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $this->service = new AuditTrailService(
            new ImportAuditLogger(new ImportLogRepository($connection)),
            new ImportLogRepository($connection),
            new ImportJobRepository($connection),
        );
    }

    public function testRecordImportStartedWritesAnInfoEventWithNoActor(): void
    {
        $this->service->recordImportStarted(5, 1);

        self::assertSame([5, 'import_started', 'Import processing started.', null], $this->state['logs'][0]);
    }

    public function testRecordDuplicateFoundWritesAWarningEventWithTheResultsSummary(): void
    {
        $result = new DuplicateCheckResult(['S001' => [2, 4]], [], null, [], 'hash');

        $this->service->recordDuplicateFound(5, 1, $result);

        self::assertSame(5, $this->state['logs'][0][0]);
        self::assertSame('duplicate_found', $this->state['logs'][0][1]);
        self::assertStringContainsString('Duplicate student_id "S001"', (string) $this->state['logs'][0][2]);
    }

    public function testRecordRetryWritesAnInfoEvent(): void
    {
        $this->service->recordRetry(5, 1, 42);

        self::assertSame([5, 'retry', 'Import job retry requested.', 42], $this->state['logs'][0]);
    }

    public function testRecordRollbackWritesAnErrorEventWithASafeGenericReasonByDefault(): void
    {
        $this->service->recordRollback(5, 1);

        self::assertSame(5, $this->state['logs'][0][0]);
        self::assertSame('rollback', $this->state['logs'][0][1]);
        self::assertSame('Database transaction rolled back during commit.', $this->state['logs'][0][2]);
        self::assertStringNotContainsString('SQLSTATE', (string) $this->state['logs'][0][2]);
    }

    public function testTimelineForReconstructsEveryPersistedRowAsATypedAuditEventOldestFirst(): void
    {
        $this->state['jobs'][5] = ['id' => 5, 'school_id' => 7];
        $this->state['logs'] = [
            [5, 'queued', null, 1, '2026-07-03 09:00:00'],
            [5, 'import_started', 'Import processing started.', null, '2026-07-03 09:00:01'],
            [5, 'parsed', null, null, '2026-07-03 09:00:02'],
            [5, 'duplicate_found', 'Duplicate student_id "S001"...', null, '2026-07-03 09:00:03'],
            [5, 'rejected', 'Duplicate student_id "S001"...', null, '2026-07-03 09:00:04'],
        ];

        $timeline = $this->service->timelineFor(5);

        self::assertCount(5, $timeline);
        self::assertContainsOnlyInstancesOf(AuditEvent::class, $timeline);
        self::assertSame(
            ['queued', 'import_started', 'parsed', 'duplicate_found', 'rejected'],
            array_map(static fn (AuditEvent $event): string => $event->event, $timeline),
        );
        // Every reconstructed event carries the parent job's school_id, even though import_logs
        // itself has no school_id column.
        foreach ($timeline as $event) {
            self::assertSame(7, $event->schoolId);
        }
    }

    #[DataProvider('eventStatusMappings')]
    public function testStatusForDerivesTheExpectedStatusForEachEvent(string $event, string $expectedStatus): void
    {
        self::assertSame($expectedStatus, AuditTrailService::statusFor($event));
    }

    /** @return array<int, array{0: string, 1: string}> */
    public static function eventStatusMappings(): array
    {
        return [
            [AuditEvent::EVENT_QUEUED, AuditEvent::STATUS_INFO],
            [AuditEvent::EVENT_PARSED, AuditEvent::STATUS_INFO],
            [AuditEvent::EVENT_MAPPED, AuditEvent::STATUS_INFO],
            [AuditEvent::EVENT_IMPORT_STARTED, AuditEvent::STATUS_INFO],
            [AuditEvent::EVENT_RETRY, AuditEvent::STATUS_INFO],
            [AuditEvent::EVENT_VALIDATED, AuditEvent::STATUS_SUCCESS],
            [AuditEvent::EVENT_COMMITTED, AuditEvent::STATUS_SUCCESS],
            [AuditEvent::EVENT_REJECTED, AuditEvent::STATUS_ERROR],
            [AuditEvent::EVENT_ROLLBACK, AuditEvent::STATUS_ERROR],
            [AuditEvent::EVENT_DUPLICATE_FOUND, AuditEvent::STATUS_WARNING],
        ];
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        if (str_starts_with($sql, 'INSERT INTO import_logs')) {
            $this->state['logs'][] = $params;
            $statement->method('rowCount')->willReturn(1);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'WHERE id = ?')) {
            $row = $this->state['jobs'][(int) $params[0]] ?? false;
            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_logs') && str_contains($sql, 'import_job_id = ?')) {
            $jobId = (int) $params[0];
            $rows = [];

            foreach ($this->state['logs'] as $log) {
                if ((int) $log[0] === $jobId) {
                    $rows[] = [
                        'import_job_id' => $log[0],
                        'event' => $log[1],
                        'message' => $log[2],
                        'actor_id' => $log[3],
                        'created_at' => $log[4] ?? '2026-07-03 09:00:00',
                    ];
                }
            }

            $statement->method('fetchAll')->willReturn($rows);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in AuditTrailServiceTest mock: %s', $sql));
    }
}
