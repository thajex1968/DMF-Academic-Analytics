<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

final class AiConfigTest extends TestCase
{
    private const KEYS = [
        'AI_DEFAULT_PROVIDER', 'AI_TIMEOUT', 'AI_TEMPERATURE', 'AI_MAX_TOKENS', 'AI_RETRY', 'AI_PROMPT_VERSION',
    ];

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            putenv($key);
        }
    }

    public function testDefaultsApplyWhenNoEnvironmentVariableIsSet(): void
    {
        foreach (self::KEYS as $key) {
            putenv($key);
        }

        $config = require dirname(__DIR__, 3) . '/config/ai.php';

        self::assertSame('mock', $config['default_provider']);
        self::assertSame(30, $config['timeout']);
        self::assertSame(0.2, $config['temperature']);
        self::assertSame(2048, $config['max_tokens']);
        self::assertSame(0, $config['retry']);
        self::assertSame('v1', $config['prompt_version']);
    }

    public function testEnvironmentVariablesOverrideDefaultsAndAreCastToTheRightType(): void
    {
        putenv('AI_DEFAULT_PROVIDER=openai');
        putenv('AI_TIMEOUT=60');
        putenv('AI_TEMPERATURE=0.7');
        putenv('AI_MAX_TOKENS=4096');
        putenv('AI_RETRY=3');
        putenv('AI_PROMPT_VERSION=v2');

        $config = require dirname(__DIR__, 3) . '/config/ai.php';

        self::assertSame('openai', $config['default_provider']);
        self::assertSame(60, $config['timeout']);
        self::assertSame(0.7, $config['temperature']);
        self::assertSame(4096, $config['max_tokens']);
        self::assertSame(3, $config['retry']);
        self::assertSame('v2', $config['prompt_version']);
    }

    public function testNoApiKeyOrOtherSecretIsHardcodedInTheConfigFile(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3) . '/config/ai.php');

        self::assertIsString($source);
        self::assertStringNotContainsString('sk-', $source);
        self::assertMatchesRegularExpression('/getenv\(.AI_/', $source);
    }
}
