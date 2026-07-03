<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Audit;

use DateTimeImmutable;
use DMF\Import\Audit\AuditEvent;
use DMF\Import\Audit\ImportAuditLogger;
use DMF\Repository\ImportLogRepository;
use Dmf\Core\Contract\ConnectionInterface;
use InvalidArgumentException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ImportAuditLoggerTest extends TestCase
{
    public function testRecordPersistsAWritableEventIntoImportLogs(): void
    {
        $captured = null;

        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturnCallback(
            function (string $sql, array $params = []) use (&$captured, $statement): PDOStatement {
                $captured = [$sql, $params];

                return $statement;
            },
        );

        $logger = new ImportAuditLogger(new ImportLogRepository($connection));

        $logger->record(new AuditEvent(
            5,
            AuditEvent::EVENT_DUPLICATE_FOUND,
            AuditEvent::STATUS_WARNING,
            1,
            null,
            'Duplicate detected.',
            [],
            new DateTimeImmutable(),
        ));

        self::assertNotNull($captured);
        self::assertStringContainsString('INSERT INTO import_logs', $captured[0]);
        self::assertSame([5, 'duplicate_found', 'Duplicate detected.', null], $captured[1]);
    }

    #[DataProvider('writableEvents')]
    public function testAcceptsEveryEventT26Added(string $event): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('1');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturn($statement);

        $logger = new ImportAuditLogger(new ImportLogRepository($connection));

        $logger->record(
            new AuditEvent(1, $event, AuditEvent::STATUS_INFO, null, null, 'x', [], new DateTimeImmutable()),
        );

        $this->addToAssertionCount(1);
    }

    /** @return array<int, string[]> */
    public static function writableEvents(): array
    {
        return [
            [AuditEvent::EVENT_DUPLICATE_FOUND],
            [AuditEvent::EVENT_IMPORT_STARTED],
            [AuditEvent::EVENT_RETRY],
            [AuditEvent::EVENT_ROLLBACK],
        ];
    }

    #[DataProvider('preExistingEvents')]
    public function testRefusesToWriteAnEventImportJobManagerAlreadyOwns(string $event): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $logger = new ImportAuditLogger(new ImportLogRepository($connection));

        $this->expectException(InvalidArgumentException::class);

        $logger->record(
            new AuditEvent(1, $event, AuditEvent::STATUS_INFO, null, null, 'x', [], new DateTimeImmutable()),
        );
    }

    /** @return array<int, string[]> */
    public static function preExistingEvents(): array
    {
        return [
            [AuditEvent::EVENT_QUEUED],
            [AuditEvent::EVENT_PARSED],
            [AuditEvent::EVENT_MAPPED],
            [AuditEvent::EVENT_VALIDATED],
            [AuditEvent::EVENT_COMMITTED],
            [AuditEvent::EVENT_REJECTED],
        ];
    }
}
