<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Pipeline;

use DateTimeImmutable;
use DMF\Analytics\Canonical\AnalyticsContext;
use DMF\Analytics\Canonical\AnalyticsMetadata;
use DMF\Analytics\Canonical\AssessmentAnalyticsRecord;
use DMF\Analytics\Contracts\AnalyticsCalculatorInterface;
use DMF\Analytics\Contracts\AnalyticsResultInterface;
use DMF\Analytics\Contracts\CalculatorCapabilities;
use DMF\Analytics\Contracts\CalculatorExecutionContext;
use DMF\Analytics\Contracts\CalculatorPriority;
use DMF\Analytics\Pipeline\AnalyticsPipeline;
use PHPUnit\Framework\TestCase;

final class AnalyticsPipelineTest extends TestCase
{
    private function makeContext(): AnalyticsContext
    {
        return new AnalyticsContext(
            new AnalyticsMetadata(3, 'MATH', 2569, 6, new DateTimeImmutable()),
            new AssessmentAnalyticsRecord(3, 0, 0, 0),
            [],
            [],
            [],
            [],
        );
    }

    /** @return AnalyticsCalculatorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private function makeCalculator(CalculatorPriority $priority)
    {
        $calculator = $this->createMock(AnalyticsCalculatorInterface::class);
        $calculator->method('priority')->willReturn($priority);
        $calculator->method('capabilities')->willReturn(new CalculatorCapabilities(true, true));

        return $calculator;
    }

    public function testAnEmptyPipelineProducesNoResults(): void
    {
        $pipeline = new AnalyticsPipeline([]);

        self::assertSame([], $pipeline->run($this->makeContext()));
    }

    public function testEveryCalculatorIsInvokedOnceWithAnExecutionContextWrappingTheSameAnalyticsContext(): void
    {
        $context = $this->makeContext();

        $first = $this->makeCalculator(CalculatorPriority::NORMAL);
        $firstResult = $this->createMock(AnalyticsResultInterface::class);
        $first->expects(self::once())
            ->method('calculate')
            ->with(self::isInstanceOf(CalculatorExecutionContext::class))
            ->willReturnCallback(function (CalculatorExecutionContext $executionContext) use ($context, $firstResult) {
                self::assertSame($context, $executionContext->context);

                return $firstResult;
            });

        $second = $this->makeCalculator(CalculatorPriority::NORMAL);
        $secondResult = $this->createMock(AnalyticsResultInterface::class);
        $second->expects(self::once())->method('calculate')->willReturn($secondResult);

        $pipeline = new AnalyticsPipeline([$first, $second]);

        self::assertSame([$firstResult, $secondResult], $pipeline->run($context));
    }

    public function testCalculatorsRunInPriorityOrderRatherThanRegistrationOrder(): void
    {
        $callOrder = [];

        $low = $this->makeCalculator(CalculatorPriority::LOW);
        $low->method('calculate')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'low';

            return $this->createMock(AnalyticsResultInterface::class);
        });

        $highest = $this->makeCalculator(CalculatorPriority::HIGHEST);
        $highest->method('calculate')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'highest';

            return $this->createMock(AnalyticsResultInterface::class);
        });

        $normal = $this->makeCalculator(CalculatorPriority::NORMAL);
        $normal->method('calculate')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'normal';

            return $this->createMock(AnalyticsResultInterface::class);
        });

        // Registered low -> highest -> normal, deliberately not in priority order.
        (new AnalyticsPipeline([$low, $highest, $normal]))->run($this->makeContext());

        self::assertSame(['highest', 'normal', 'low'], $callOrder);
    }

    public function testCalculatorsOfEqualPriorityPreserveTheirRegistrationOrder(): void
    {
        $callOrder = [];

        $first = $this->makeCalculator(CalculatorPriority::NORMAL);
        $first->method('calculate')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'first';

            return $this->createMock(AnalyticsResultInterface::class);
        });

        $second = $this->makeCalculator(CalculatorPriority::NORMAL);
        $second->method('calculate')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'second';

            return $this->createMock(AnalyticsResultInterface::class);
        });

        (new AnalyticsPipeline([$first, $second]))->run($this->makeContext());

        self::assertSame(['first', 'second'], $callOrder);
    }
}
