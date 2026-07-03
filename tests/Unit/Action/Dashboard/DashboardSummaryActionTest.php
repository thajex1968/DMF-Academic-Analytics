<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Action\Dashboard;

use DMF\Action\Dashboard\DashboardSummaryAction;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\SchoolRepository;
use Dmf\Core\Auth\Guard;
use Dmf\Core\Auth\Principal;
use Dmf\Core\Config\Config;
use Dmf\Core\Contract\ConnectionInterface;
use Dmf\Core\Exception\AuthException;
use Dmf\Core\Http\Request;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DashboardSummaryActionTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $jobs;

    private array $schools;

    private ConnectionInterface $connection;

    protected function setUp(): void
    {
        $this->jobs = [
            1 => [
                'id' => 1, 'school_id' => 1, 'status' => 'committed',
                'file_type' => 'xlsx', 'created_at' => '2026-07-01 09:00:00',
            ],
            2 => [
                'id' => 2, 'school_id' => 1, 'status' => 'failed',
                'file_type' => 'csv', 'created_at' => '2026-07-02 09:00:00',
            ],
            3 => [
                'id' => 3, 'school_id' => 1, 'status' => 'queued',
                'file_type' => 'xlsx', 'created_at' => '2026-07-03 09:00:00',
            ],
        ];
        $this->schools = [1 => ['id' => 1, 'name_th' => 'โรงเรียนชุมชนดงมะไฟเจริญศิลป์']];

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $this->connection = $connection;
    }

    public function testReturnsAppUserSchoolStatisticsAndSystemStatusForAnAuthenticatedPrincipal(): void
    {
        $principal = new Principal('5', 'teacher', time(), time() + 28800, [
            'username' => 'teacher01',
            'display_name' => 'Teacher One',
            'school_id' => 1,
        ]);

        $guard = $this->createMock(Guard::class);
        $guard->method('user')->with('valid-token')->willReturn($principal);

        $config = Config::fromArray([
            'app' => [
                'name' => 'DMF Learning Analytics Platform', 'version' => '0.1.0',
                'env' => 'production', 'debug' => false,
            ],
        ]);

        $action = new DashboardSummaryAction(
            $guard,
            $config,
            new SchoolRepository($this->connection),
            new ImportJobRepository($this->connection),
        );

        $response = $action(new Request('GET', 'dashboard_summary', [], 'valid-token'));
        $data = $response->data();

        self::assertSame(200, $response->statusCode());
        self::assertSame('DMF Learning Analytics Platform', $data['app']['name']);
        self::assertSame('0.1.0', $data['app']['version']);
        self::assertSame('teacher01', $data['user']['username']);
        self::assertSame('teacher', $data['user']['role']);
        self::assertSame('โรงเรียนชุมชนดงมะไฟเจริญศิลป์', $data['school']['name']);
        self::assertSame(3, $data['import_statistics']['total']);
        self::assertSame(1, $data['import_statistics']['committed']);
        self::assertSame(1, $data['import_statistics']['failed']);
        self::assertSame(1, $data['import_statistics']['queued']);
        self::assertCount(3, $data['recent_import_jobs']);
        self::assertSame('ok', $data['system_status']['database']);
        self::assertArrayHasKey('php_version', $data['system_status']);
    }

    public function testAnUnauthenticatedRequestNeverReachesRepositoryQueries(): void
    {
        $guard = $this->createMock(Guard::class);
        $guard->method('user')->willThrowException(AuthException::tokenInvalid());

        $config = Config::fromArray(['app' => []]);

        $action = new DashboardSummaryAction(
            $guard,
            $config,
            new SchoolRepository($this->connection),
            new ImportJobRepository($this->connection),
        );

        $this->expectException(AuthException::class);
        $action(new Request('GET', 'dashboard_summary', [], 'garbage'));
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        if (str_contains($sql, 'FROM schools') && str_contains($sql, 'WHERE id = ?')) {
            $row = $this->schools[(int) $params[0]] ?? false;
            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_contains($sql, 'FROM import_jobs') && str_contains($sql, 'WHERE school_id = ?')) {
            $rows = array_values(array_filter(
                $this->jobs,
                fn (array $job): bool => $job['school_id'] === (int) $params[0],
            ));
            $statement->method('fetchAll')->willReturn($rows);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in DashboardSummaryActionTest mock: %s', $sql));
    }
}
