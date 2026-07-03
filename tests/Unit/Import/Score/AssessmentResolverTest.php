<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Score;

use DMF\Import\Score\AssessmentResolver;
use DMF\Repository\AssessmentRepository;
use Dmf\Core\Contract\ConnectionInterface;
use PDOStatement;
use PHPUnit\Framework\TestCase;

/** AssessmentRepository is `final` — tested via a real instance over a mocked ConnectionInterface. */
final class AssessmentResolverTest extends TestCase
{
    public function testResolveReturnsTheAssessmentRowWhenFound(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(['id' => 3, 'subject_code' => 'MATH']);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $resolver = new AssessmentResolver(new AssessmentRepository($connection));

        self::assertSame(['id' => 3, 'subject_code' => 'MATH'], $resolver->resolve(3));
    }

    public function testResolveReturnsNullWhenNotFound(): void
    {
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetch')->willReturn(false);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('execute')->willReturn($statement);

        $resolver = new AssessmentResolver(new AssessmentRepository($connection));

        self::assertNull($resolver->resolve(999));
    }
}
