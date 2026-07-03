<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Auth;

use DMF\Auth\StaffRateLimiter;
use DMF\Repository\LoginRateLimitRepository;
use Dmf\Core\Contract\ConnectionInterface;
use Dmf\Core\Exception\AuthException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StaffRateLimiterTest extends TestCase
{
    /** @var array<int, array<string, mixed>> */
    private array $rows;

    private StaffRateLimiter $limiter;

    protected function setUp(): void
    {
        $this->rows = [];

        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturnCallback(fn (): string => (string) (count($this->rows) + 1));

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $this->limiter = new StaffRateLimiter(new LoginRateLimitRepository($connection), 5, 300);
    }

    public function testAssertNotLockedPassesWhenNoRecordExists(): void
    {
        $this->limiter->assertNotLocked('teacher01');
        $this->addToAssertionCount(1);
    }

    public function testRecordFailureBelowThresholdDoesNotLock(): void
    {
        $this->limiter->recordFailure('teacher01');
        $this->limiter->recordFailure('teacher01');
        $this->limiter->recordFailure('teacher01');
        $this->limiter->recordFailure('teacher01');

        $this->limiter->assertNotLocked('teacher01');
        $this->addToAssertionCount(1);
    }

    public function testTheFifthConsecutiveFailureLocksTheAccount(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->limiter->recordFailure('teacher01');
        }

        $this->expectException(AuthException::class);
        $this->limiter->recordFailure('teacher01');
    }

    public function testALockedAccountRejectsFurtherAttemptsUntilTheLockExpires(): void
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->limiter->recordFailure('teacher01');
            } catch (AuthException) {
                // expected on the 5th
            }
        }

        $this->expectException(AuthException::class);
        $this->limiter->assertNotLocked('teacher01');
    }

    public function testAnExpiredLockNoLongerBlocks(): void
    {
        $this->rows[1] = [
            'id' => 1,
            'username' => 'teacher01',
            'failed_attempts' => 5,
            'locked_until' => date('Y-m-d H:i:s', time() - 60),
        ];

        $this->limiter->assertNotLocked('teacher01');
        $this->addToAssertionCount(1);
    }

    public function testResetClearsFailedAttemptsAndLock(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->limiter->recordFailure('teacher01');
        }

        $this->limiter->reset('teacher01');

        self::assertSame(0, $this->rows[1]['failed_attempts']);
        self::assertNull($this->rows[1]['locked_until']);

        // A fresh sequence of 4 more failures still should not lock — the counter truly reset.
        for ($i = 0; $i < 4; $i++) {
            $this->limiter->recordFailure('teacher01');
        }

        $this->limiter->assertNotLocked('teacher01');
        $this->addToAssertionCount(1);
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        if (str_starts_with($sql, 'INSERT INTO login_rate_limits')) {
            $id = count($this->rows) + 1;
            $this->rows[$id] = [
                'id' => $id,
                'username' => $params[0],
                'failed_attempts' => $params[1],
                'locked_until' => $params[2],
            ];
            $statement->method('rowCount')->willReturn(1);

            return $statement;
        }

        if (str_starts_with($sql, 'UPDATE login_rate_limits')) {
            $id = (int) end($params);
            $columns = [];
            preg_match_all('/(\w+) = \?/', $sql, $matches);
            $columns = $matches[1];
            $values = array_slice($params, 0, count($columns));

            foreach ($columns as $index => $column) {
                if ($column === 'updated_at') {
                    continue;
                }

                $this->rows[$id][$column] = $values[$index];
            }

            $statement->method('rowCount')->willReturn(1);

            return $statement;
        }

        if (str_contains($sql, 'FROM login_rate_limits') && str_contains($sql, 'username = ?')) {
            foreach ($this->rows as $row) {
                if ($row['username'] === $params[0]) {
                    $statement->method('fetch')->willReturn($row);

                    return $statement;
                }
            }

            $statement->method('fetch')->willReturn(false);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in StaffRateLimiterTest mock: %s', $sql));
    }
}
