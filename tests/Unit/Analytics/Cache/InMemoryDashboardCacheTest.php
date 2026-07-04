<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Analytics\Cache;

use DMF\Analytics\Cache\DashboardCacheInterface;
use DMF\Analytics\Cache\InMemoryDashboardCache;
use PHPUnit\Framework\TestCase;

final class InMemoryDashboardCacheTest extends TestCase
{
    public function testSetThenGetReturnsTheStoredValue(): void
    {
        $cache = new InMemoryDashboardCache();
        self::assertInstanceOf(DashboardCacheInterface::class, $cache);

        $cache->set('key', 'value', 60);

        self::assertTrue($cache->has('key'));
        self::assertSame('value', $cache->get('key'));
    }

    public function testGetReturnsTheDefaultWhenTheKeyWasNeverSet(): void
    {
        $cache = new InMemoryDashboardCache();

        self::assertNull($cache->get('missing'));
        self::assertSame('fallback', $cache->get('missing', 'fallback'));
        self::assertFalse($cache->has('missing'));
    }

    public function testAnExpiredEntryIsTreatedAsMissing(): void
    {
        $cache = new InMemoryDashboardCache();

        $cache->set('key', 'value', -1);

        self::assertFalse($cache->has('key'));
        self::assertNull($cache->get('key'));
    }

    public function testDeleteRemovesOneEntryWithoutAffectingOthers(): void
    {
        $cache = new InMemoryDashboardCache();
        $cache->set('a', 1);
        $cache->set('b', 2);

        $cache->delete('a');

        self::assertFalse($cache->has('a'));
        self::assertTrue($cache->has('b'));
    }

    public function testClearRemovesEveryEntry(): void
    {
        $cache = new InMemoryDashboardCache();
        $cache->set('a', 1);
        $cache->set('b', 2);

        $cache->clear();

        self::assertFalse($cache->has('a'));
        self::assertFalse($cache->has('b'));
    }

    public function testSetManyAndGetManyRoundTripEveryValue(): void
    {
        $cache = new InMemoryDashboardCache();

        $cache->setMany(['a' => 1, 'b' => 2], 60);

        self::assertSame(['a' => 1, 'b' => 2, 'c' => null], $cache->getMany(['a', 'b', 'c']));
    }
}
