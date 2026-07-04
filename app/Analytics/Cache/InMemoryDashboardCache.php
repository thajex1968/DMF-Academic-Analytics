<?php

declare(strict_types=1);

namespace DMF\Analytics\Cache;

/**
 * The only DashboardCacheInterface implementation this Sprint builds — a
 * plain in-process array, no Redis, no external cache
 * (docs/02-System-Architecture.md §16's shared-hosting constraint). Every
 * consumer takes this as an optional dependency, so the Dashboard API
 * still works correctly, just uncached, when this is never constructed at
 * all (decisions/IDR-011 §4).
 */
final class InMemoryDashboardCache implements DashboardCacheInterface
{
    /** @var array<string, array{value: mixed, expiresAt: int}> */
    private array $entries = [];

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->entries[$key]['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->entries[$key] = ['value' => $value, 'expiresAt' => time() + $ttl];
    }

    public function delete(string $key): void
    {
        unset($this->entries[$key]);
    }

    public function has(string $key): bool
    {
        if (!isset($this->entries[$key])) {
            return false;
        }

        if ($this->entries[$key]['expiresAt'] < time()) {
            unset($this->entries[$key]);

            return false;
        }

        return true;
    }

    public function clear(): void
    {
        $this->entries = [];
    }

    /** @param array<string, mixed> $values */
    public function setMany(array $values, int $ttl = 3600): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
    }

    /**
     * @param string[] $keys
     * @return array<string, mixed>
     */
    public function getMany(array $keys): array
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }

        return $result;
    }
}
