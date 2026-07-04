<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Dashboard;

use DMF\Action\Dashboard\DashboardHealthAction;
use DMF\Analytics\Aggregation\DashboardHealthAggregator;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\StudentRepository;
use Dmf\Core\Contract\ConnectionInterface;
use Dmf\Core\Http\Request;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DashboardHealthActionTest extends TestCase
{
    public function testReturnsTheHealthSnapshotAsJson(): void
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturnCallback(
            function (string $sql, array $params = []): PDOStatement {
                $statement = $this->createMock(PDOStatement::class);

                if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'FROM students')) {
                    $statement->method('fetch')->willReturn(['n' => 120]);

                    return $statement;
                }

                if (str_contains($sql, 'COUNT(*)') && str_contains($sql, 'FROM assessments')) {
                    $statement->method('fetch')->willReturn(['n' => 3]);

                    return $statement;
                }

                if (str_contains($sql, 'ORDER BY academic_year DESC')) {
                    $statement->method('fetch')->willReturn(
                        ['id' => 3, 'subject_code' => 'MATH', 'academic_year' => 2569],
                    );

                    return $statement;
                }

                if (str_contains($sql, 'FROM import_jobs')) {
                    $statement->method('fetchAll')->willReturn([]);

                    return $statement;
                }

                throw new RuntimeException(sprintf('Unhandled SQL in DashboardHealthActionTest mock: %s', $sql));
            },
        );

        $aggregator = new DashboardHealthAggregator(
            new ImportJobRepository($connection),
            new AssessmentRepository($connection),
            new StudentRepository($connection),
        );
        $action = new DashboardHealthAction($aggregator, new DashboardResponseSerializer());

        $response = $action(new Request('GET', 'dashboard_health', []));
        $data = $response->data();

        self::assertSame(200, $response->statusCode());
        self::assertSame('ok', $data['import_status']);
        self::assertSame('ok', $data['analytics_status']);
        self::assertSame(3, $data['latest_assessment']['assessment_id']);
        self::assertSame(120, $data['total_students']);
        self::assertSame(3, $data['total_assessments']);
        self::assertSame([], $data['warnings']);
    }
}
