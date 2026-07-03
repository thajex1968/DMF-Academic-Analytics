<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Score;

use DMF\Import\Score\StudentResolver;
use DMF\Repository\StudentRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/** StudentRepository is `final` — tested via a real instance over a mocked ConnectionInterface. */
final class StudentResolverTest extends TestCase
{
    public function testResolveReturnsTheStudentRowWhenFound(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['student_id' => 'S001', 'full_name' => 'Somchai']);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $resolver = new StudentResolver(new StudentRepository($connection));

        self::assertSame(['student_id' => 'S001', 'full_name' => 'Somchai'], $resolver->resolve('S001'));
    }

    public function testResolveReturnsNullWhenNotFound(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $resolver = new StudentResolver(new StudentRepository($connection));

        self::assertNull($resolver->resolve('S404'));
    }
}
