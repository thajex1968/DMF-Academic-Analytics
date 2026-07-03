<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Template;

use DMF\Import\Template\ExampleTemplates;
use DMF\Import\Template\TemplateRegistry;
use DMF\Import\Template\TemplateResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TemplateResolverTest extends TestCase
{
    public function testResolvesTheConfiguredDefaultTemplateRegardlessOfAssessmentId(): void
    {
        $template = ExampleTemplates::studentIdAndScore();
        $registry = new TemplateRegistry();
        $registry->register($template);

        $resolver = new TemplateResolver($registry, $template->key);

        self::assertSame($template, $resolver->resolveForAssessment(3));
        self::assertSame($template, $resolver->resolveForAssessment(999), 'v1.0 has no per-assessment lookup yet');
    }

    public function testPropagatesTheRegistrysExceptionWhenTheDefaultKeyIsNotRegistered(): void
    {
        $registry = new TemplateRegistry();
        $resolver = new TemplateResolver($registry, 'NOT-REGISTERED');

        $this->expectException(RuntimeException::class);

        $resolver->resolveForAssessment(3);
    }
}
