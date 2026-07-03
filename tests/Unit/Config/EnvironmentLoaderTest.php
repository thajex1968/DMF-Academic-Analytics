<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Config;

use DMF\Config\EnvironmentLoader;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

final class EnvironmentLoaderTest extends TestCase
{
    /** @var list<string> */
    private array $envKeysToClean = [];

    private ?string $tempFile = null;

    #[After]
    public function cleanUpEnvironment(): void
    {
        foreach ($this->envKeysToClean as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
        $this->envKeysToClean = [];

        if ($this->tempFile !== null && is_file($this->tempFile)) {
            unlink($this->tempFile);
        }
        $this->tempFile = null;
    }

    public function testLoadsSimpleKeyValuePairs(): void
    {
        $this->givenEnvFile([
            'DLAP_TEST_HOST=localhost',
            'DLAP_TEST_PORT=3306',
        ]);
        $this->trackKeys(['DLAP_TEST_HOST', 'DLAP_TEST_PORT']);

        EnvironmentLoader::load($this->tempFile);

        self::assertSame('localhost', getenv('DLAP_TEST_HOST'));
        self::assertSame('3306', $_ENV['DLAP_TEST_PORT']);
        self::assertSame('3306', $_SERVER['DLAP_TEST_PORT']);
    }

    public function testStripsMatchingDoubleAndSingleQuotes(): void
    {
        $this->givenEnvFile([
            'DLAP_TEST_DOUBLE="hello world"',
            "DLAP_TEST_SINGLE='hello world'",
            'DLAP_TEST_MISMATCHED="hello\'',
        ]);
        $this->trackKeys(['DLAP_TEST_DOUBLE', 'DLAP_TEST_SINGLE', 'DLAP_TEST_MISMATCHED']);

        EnvironmentLoader::load($this->tempFile);

        self::assertSame('hello world', getenv('DLAP_TEST_DOUBLE'));
        self::assertSame('hello world', getenv('DLAP_TEST_SINGLE'));
        self::assertSame('"hello\'', getenv('DLAP_TEST_MISMATCHED'));
    }

    public function testSkipsCommentsAndBlankLines(): void
    {
        $this->givenEnvFile([
            '# a comment',
            '',
            '   ',
            'DLAP_TEST_AFTER_COMMENT=value',
        ]);
        $this->trackKeys(['DLAP_TEST_AFTER_COMMENT']);

        EnvironmentLoader::load($this->tempFile);

        self::assertSame('value', getenv('DLAP_TEST_AFTER_COMMENT'));
    }

    public function testSkipsMalformedLinesWithoutError(): void
    {
        $this->givenEnvFile([
            'THIS_LINE_HAS_NO_EQUALS_SIGN',
            'DLAP_TEST_VALID=value',
        ]);
        $this->trackKeys(['DLAP_TEST_VALID']);

        EnvironmentLoader::load($this->tempFile);

        self::assertSame('value', getenv('DLAP_TEST_VALID'));
    }

    public function testNeverOverwritesAnAlreadySetVariable(): void
    {
        $this->trackKeys(['DLAP_TEST_PRECEDENCE']);
        putenv('DLAP_TEST_PRECEDENCE=already-set-by-host');
        $_ENV['DLAP_TEST_PRECEDENCE'] = 'already-set-by-host';

        $this->givenEnvFile(['DLAP_TEST_PRECEDENCE=from-dotenv-file']);
        EnvironmentLoader::load($this->tempFile);

        self::assertSame('already-set-by-host', getenv('DLAP_TEST_PRECEDENCE'));
    }

    public function testMissingFileIsANoOp(): void
    {
        // Must not throw for a path that does not exist.
        EnvironmentLoader::load(sys_get_temp_dir() . '/dlap-env-loader-test-does-not-exist.env');

        $this->addToAssertionCount(1);
    }

    /** @param list<string> $lines */
    private function givenEnvFile(array $lines): void
    {
        $path = tempnam(sys_get_temp_dir(), 'dlap-env-');
        self::assertIsString($path);
        file_put_contents($path, implode("\n", $lines) . "\n");
        $this->tempFile = $path;
    }

    /** @param list<string> $keys */
    private function trackKeys(array $keys): void
    {
        $this->envKeysToClean = [...$this->envKeysToClean, ...$keys];
    }
}
