<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Database;

use DMF\Database\ConnectionFactory;
use Dmf\Core\Config\Config;
use Dmf\Core\Database\Connection;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ConnectionFactoryTest extends TestCase
{
    public function testBuildsAConnectionFromTheDatabaseConfigGroup(): void
    {
        $config = Config::fromArray([
            'database' => [
                'host' => 'db.example.test',
                'port' => 3307,
                'database' => 'dmf_academic',
                'username' => 'dlap',
                'password' => 'secret',
                'options' => [],
            ],
        ]);

        $connection = ConnectionFactory::fromConfig($config);

        self::assertInstanceOf(Connection::class, $connection);
        self::assertSame('db.example.test', $this->readPrivate($connection, 'host'));
        self::assertSame('dmf_academic', $this->readPrivate($connection, 'database'));
        self::assertSame('dlap', $this->readPrivate($connection, 'username'));
        self::assertSame('secret', $this->readPrivate($connection, 'password'));
        self::assertSame(3307, $this->readPrivate($connection, 'port'));
    }

    public function testFallsBackToSafeDefaultsWhenTheDatabaseGroupIsMissing(): void
    {
        $config = Config::fromArray([]);

        $connection = ConnectionFactory::fromConfig($config);

        self::assertSame('localhost', $this->readPrivate($connection, 'host'));
        self::assertSame('', $this->readPrivate($connection, 'database'));
        self::assertSame(3306, $this->readPrivate($connection, 'port'));
    }

    public function testNeverOpensARealConnectionDuringConstruction(): void
    {
        // Connection::pdo() is lazy — constructing it must never attempt network I/O, so this
        // must succeed instantly even with unreachable connection details.
        $config = Config::fromArray([
            'database' => ['host' => 'unreachable.invalid', 'database' => 'x', 'username' => 'x', 'password' => 'x'],
        ]);

        $connection = ConnectionFactory::fromConfig($config);

        self::assertInstanceOf(Connection::class, $connection);
    }

    private function readPrivate(Connection $connection, string $property): mixed
    {
        $reflection = new ReflectionProperty(Connection::class, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($connection);
    }
}
