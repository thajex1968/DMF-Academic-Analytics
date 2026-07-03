<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Auth;

use DMF\Auth\StaffGuard;
use DMF\Auth\StaffRateLimiter;
use DMF\Auth\StaffTokenManager;
use DMF\Repository\LoginRateLimitRepository;
use DMF\Repository\StaffUserRepository;
use Dmf\Core\Contract\ConnectionInterface;
use Dmf\Core\Exception\AuthException;
use Dmf\Core\Security\PasswordHasher;
use PDO;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * StaffGuard exercised end to end: real StaffTokenManager + StaffRateLimiter
 * over one mocked ConnectionInterface — proves login()/user()/logout()
 * actually compose correctly, not just that each piece works alone.
 */
final class StaffGuardTest extends TestCase
{
    /** @var array<string, array<string, mixed>> */
    private array $users;

    /** @var array<int, array<string, mixed>> */
    private array $limits;

    private StaffGuard $guard;

    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new PasswordHasher(cost: 4);
        $this->limits = [];

        $this->users = [
            'teacher01' => [
                'id' => 5,
                'username' => 'teacher01',
                'password_hash' => $this->hasher->hash('correct-password'),
                'display_name' => 'Teacher One',
                'role' => 'teacher',
                'school_id' => 1,
                'is_active' => 1,
                'deleted_at' => null,
            ],
        ];

        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturnCallback(fn (): string => (string) (count($this->limits) + 1));

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $tokens = new StaffTokenManager('test-secret', 28800, new StaffUserRepository($connection), $this->hasher);
        $limiter = new StaffRateLimiter(new LoginRateLimitRepository($connection), 5, 300);

        $this->guard = new StaffGuard($tokens, $limiter);
    }

    public function testLoginWithValidCredentialsReturnsAToken(): void
    {
        $token = $this->guard->login(['username' => 'teacher01', 'password' => 'correct-password']);

        self::assertNotSame('', $token);

        $principal = $this->guard->user($token);

        self::assertSame('5', $principal->sub);
        self::assertSame('teacher', $principal->role);
    }

    public function testLoginWithWrongPasswordRecordsAFailureAndThrows(): void
    {
        try {
            $this->guard->login(['username' => 'teacher01', 'password' => 'wrong']);
            self::fail('Expected AuthException.');
        } catch (AuthException) {
        }

        self::assertSame(1, $this->limits[1]['failed_attempts']);
    }

    public function testFiveFailedLoginsLockTheAccountAndBlockFurtherAttemptsEvenWithTheCorrectPassword(): void
    {
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->guard->login(['username' => 'teacher01', 'password' => 'wrong']);
            } catch (AuthException) {
                // expected every time
            }
        }

        $this->expectException(AuthException::class);
        $this->guard->login(['username' => 'teacher01', 'password' => 'correct-password']);
    }

    public function testASuccessfulLoginResetsThePriorFailureCount(): void
    {
        try {
            $this->guard->login(['username' => 'teacher01', 'password' => 'wrong']);
        } catch (AuthException) {
        }

        self::assertSame(1, $this->limits[1]['failed_attempts']);

        $this->guard->login(['username' => 'teacher01', 'password' => 'correct-password']);

        self::assertSame(0, $this->limits[1]['failed_attempts']);
    }

    public function testUserRejectsAnInvalidToken(): void
    {
        $this->expectException(AuthException::class);

        $this->guard->user('not-a-real-token');
    }

    public function testLogoutDoesNotThrowForAStatelessToken(): void
    {
        $token = $this->guard->login(['username' => 'teacher01', 'password' => 'correct-password']);

        $this->guard->logout($token);
        $this->addToAssertionCount(1);
    }

    /** @param array<int, mixed> $params */
    private function fakeExecute(string $sql, array $params): PDOStatement
    {
        $statement = $this->createMock(PDOStatement::class);

        if (str_contains($sql, 'FROM staff_users') && str_contains($sql, 'WHERE username = ?')) {
            $row = $this->users[(string) $params[0]] ?? false;
            $statement->method('fetch')->willReturn($row);

            return $statement;
        }

        if (str_starts_with($sql, 'INSERT INTO login_rate_limits')) {
            $id = count($this->limits) + 1;
            $this->limits[$id] = [
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
            preg_match_all('/(\w+) = \?/', $sql, $matches);
            $columns = $matches[1];
            $values = array_slice($params, 0, count($columns));

            foreach ($columns as $index => $column) {
                if ($column === 'updated_at' || $column === 'id') {
                    continue;
                }

                $this->limits[$id][$column] = $values[$index];
            }

            $statement->method('rowCount')->willReturn(1);

            return $statement;
        }

        if (str_contains($sql, 'FROM login_rate_limits') && str_contains($sql, 'username = ?')) {
            foreach ($this->limits as $row) {
                if ($row['username'] === $params[0]) {
                    $statement->method('fetch')->willReturn($row);

                    return $statement;
                }
            }

            $statement->method('fetch')->willReturn(false);

            return $statement;
        }

        throw new RuntimeException(sprintf('Unhandled SQL in StaffGuardTest mock: %s', $sql));
    }
}
