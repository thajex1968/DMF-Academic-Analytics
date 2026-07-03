<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Auth;

use DMF\Auth\StaffTokenManager;
use DMF\Repository\StaffUserRepository;
use Dmf\Core\Contract\ConnectionInterface;
use Dmf\Core\Exception\AuthException;
use Dmf\Core\Security\PasswordHasher;
use PDOStatement;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StaffTokenManagerTest extends TestCase
{
    private const SECRET = 'test-secret';

    /** @var array<string, array<string, mixed>> */
    private array $users;

    private StaffTokenManager $manager;

    private PasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new PasswordHasher(cost: 4);

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
            'inactive01' => [
                'id' => 6,
                'username' => 'inactive01',
                'password_hash' => $this->hasher->hash('correct-password'),
                'display_name' => 'Inactive One',
                'role' => 'teacher',
                'school_id' => 1,
                'is_active' => 0,
                'deleted_at' => null,
            ],
            'deleted01' => [
                'id' => 7,
                'username' => 'deleted01',
                'password_hash' => $this->hasher->hash('correct-password'),
                'display_name' => 'Deleted One',
                'role' => 'teacher',
                'school_id' => 1,
                'is_active' => 1,
                'deleted_at' => '2026-01-01 00:00:00',
            ],
        ];

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturnCallback(
            fn (string $sql, array $params = []): PDOStatement => $this->fakeExecute($sql, $params),
        );

        $this->manager = new StaffTokenManager(
            self::SECRET,
            28800,
            new StaffUserRepository($connection),
            $this->hasher,
        );
    }

    public function testAuthenticateWithValidCredentialsIssuesAVerifiableToken(): void
    {
        $token = $this->manager->authenticate(['username' => 'teacher01', 'password' => 'correct-password']);

        $payload = $this->manager->verify($token);

        self::assertSame('5', $payload['sub']);
        self::assertSame('teacher', $payload['role']);
        self::assertSame('teacher01', $payload['username']);
        self::assertSame('Teacher One', $payload['display_name']);
        self::assertSame(1, $payload['school_id']);
    }

    public function testAuthenticateWithWrongPasswordThrows(): void
    {
        $this->expectException(AuthException::class);

        $this->manager->authenticate(['username' => 'teacher01', 'password' => 'wrong-password']);
    }

    public function testAuthenticateWithUnknownUsernameThrows(): void
    {
        $this->expectException(AuthException::class);

        $this->manager->authenticate(['username' => 'nobody', 'password' => 'anything']);
    }

    public function testUnknownUsernameAndWrongPasswordProduceTheSameGenericMessage(): void
    {
        // No username-enumeration signal: both failure modes must be indistinguishable.
        try {
            $this->manager->authenticate(['username' => 'nobody', 'password' => 'anything']);
            self::fail('Expected AuthException.');
        } catch (AuthException $unknownUserMessage) {
        }

        try {
            $this->manager->authenticate(['username' => 'teacher01', 'password' => 'wrong-password']);
            self::fail('Expected AuthException.');
        } catch (AuthException $wrongPasswordMessage) {
        }

        self::assertSame($unknownUserMessage->getMessage(), $wrongPasswordMessage->getMessage());
    }

    public function testAuthenticateWithAnInactiveAccountThrows(): void
    {
        $this->expectException(AuthException::class);

        $this->manager->authenticate(['username' => 'inactive01', 'password' => 'correct-password']);
    }

    public function testAuthenticateWithASoftDeletedAccountThrows(): void
    {
        $this->expectException(AuthException::class);

        $this->manager->authenticate(['username' => 'deleted01', 'password' => 'correct-password']);
    }

    public function testAuthenticateWithAnEmptyPasswordThrows(): void
    {
        $this->expectException(AuthException::class);

        $this->manager->authenticate(['username' => 'teacher01', 'password' => '']);
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

        throw new RuntimeException(sprintf('Unhandled SQL in StaffTokenManagerTest mock: %s', $sql));
    }
}
