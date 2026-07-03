<?php

declare(strict_types=1);

require __DIR__ . '/../../../vendor/autoload.php';

(new \DMF\Tests\Fixtures\Import\GoldenDatasetGenerator(__DIR__))->generate();
