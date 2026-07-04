<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Result;

use DMF\Analytics\Result\AnalyticsIssue;
use PHPUnit\Framework\TestCase;

final class AnalyticsIssueTest extends TestCase
{
    public function testConstructorAssignsEveryProperty(): void
    {
        $issue = new AnalyticsIssue('question:1001', 'No responses available for this question.');

        self::assertSame('question:1001', $issue->identifier);
        self::assertSame('No responses available for this question.', $issue->message);
    }
}
