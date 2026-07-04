<?php

declare(strict_types=1);

namespace DMF\Analytics\Cache;

use Dmf\Core\Contract\CacheInterface;

/**
 * Dashboard-scoped cache contract — deliberately shaped exactly like
 * `dmf-core`'s own `Contract\CacheInterface` (get/set/delete/has/clear,
 * TTL on `set()`, `delete()` as invalidate) since that abstraction already
 * exists; this is a zero-new-method marker interface so Dashboard code
 * depends on a contract it names itself, without reinventing cache
 * semantics `dmf-core` already provides (see decisions/IDR-011 §4).
 */
interface DashboardCacheInterface extends CacheInterface
{
}
