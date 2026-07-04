<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Repository;

use DMF\Repository\AnalyticsReadRepository;
use Dmf\Core\Contract\ConnectionInterface;
use LogicException;
use PDOStatement;
use PHPUnit\Framework\TestCase;

final class AnalyticsReadRepositoryTest extends TestCase
{
    public function testFindResponsesForAssessmentJoinsQuestionsByAssessmentId(): void
    {
        $rows = [
            ['student_id' => 'S001', 'question_id' => 101, 'selected_choice' => '1', 'is_correct' => 1],
        ];
        $statement = $this->createMock(PDOStatement::class);
        $statement->method('fetchAll')->willReturn($rows);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::once())
            ->method('execute')
            ->with(
                self::logicalAnd(
                    self::stringContains('FROM student_question_responses'),
                    self::stringContains('JOIN questions'),
                    self::stringContains('WHERE q.assessment_id = ?'),
                ),
                [3],
            )
            ->willReturn($statement);

        $repository = new AnalyticsReadRepository($connection);

        self::assertSame($rows, $repository->findResponsesForAssessment(3));
    }

    public function testCreateThrowsBecauseThisRepositoryIsReadOnly(): void
    {
        $repository = new AnalyticsReadRepository($this->createMock(ConnectionInterface::class));

        $this->expectException(LogicException::class);
        $repository->create(['student_id' => 'S001']);
    }

    public function testUpdateThrowsBecauseThisRepositoryIsReadOnly(): void
    {
        $repository = new AnalyticsReadRepository($this->createMock(ConnectionInterface::class));

        $this->expectException(LogicException::class);
        $repository->update(1, ['student_id' => 'S001']);
    }

    public function testDeleteThrowsBecauseThisRepositoryIsReadOnly(): void
    {
        $repository = new AnalyticsReadRepository($this->createMock(ConnectionInterface::class));

        $this->expectException(LogicException::class);
        $repository->delete(1);
    }
}
