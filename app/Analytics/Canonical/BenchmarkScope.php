<?php

declare(strict_types=1);

namespace DMF\Analytics\Canonical;

/** The comparison tier a BenchmarkAnalyticsRecord's value represents. */
enum BenchmarkScope: string
{
    case SCHOOL = 'school';
    case PROVINCE = 'province';
    case REGION = 'region';
    case COUNTRY = 'country';
}
