<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import\Template;

use DMF\Import\Template\ColumnMapping;
use DMF\Import\Template\ImportTemplate;
use DMF\Import\Template\TemplateRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TemplateRegistryTest extends TestCase
{
    public function testRegisterAndGetRoundTrip(): void
    {
        $template = $this->makeTemplate('ONET-2569');
        $registry = new TemplateRegistry();

        $registry->register($template);

        self::assertSame($template, $registry->get('ONET-2569'));
    }

    public function testHasReflectsRegistrationState(): void
    {
        $registry = new TemplateRegistry();

        self::assertFalse($registry->has('ONET-2569'));

        $registry->register($this->makeTemplate('ONET-2569'));

        self::assertTrue($registry->has('ONET-2569'));
    }

    public function testGetThrowsForAnUnregisteredKey(): void
    {
        $registry = new TemplateRegistry();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No import template is registered for key "NT"');

        $registry->get('NT');
    }

    public function testKeysListsEveryRegisteredTemplate(): void
    {
        $registry = new TemplateRegistry();
        $registry->register($this->makeTemplate('ONET-2569'));
        $registry->register($this->makeTemplate('ONET-2570'));

        self::assertSame(['ONET-2569', 'ONET-2570'], $registry->keys());
    }

    public function testRegisteringTheSameKeyTwiceReplacesTheEarlierTemplate(): void
    {
        $registry = new TemplateRegistry();
        $registry->register($this->makeTemplate('ONET-2569', 'v1'));
        $registry->register($this->makeTemplate('ONET-2569', 'v2'));

        self::assertSame('v2', $registry->get('ONET-2569')->mappingVersion);
    }

    private function makeTemplate(string $key, string $version = 'v1'): ImportTemplate
    {
        return new ImportTemplate(
            key: $key,
            mappingVersion: $version,
            mapping: new ColumnMapping(['student_id' => ['รหัสนักเรียน']]),
            requiredColumns: ['student_id'],
            optionalColumns: [],
            validationRules: [],
        );
    }
}
