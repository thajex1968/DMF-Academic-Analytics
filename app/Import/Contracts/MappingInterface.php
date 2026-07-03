<?php

declare(strict_types=1);

namespace DMF\Import\Contracts;

use DMF\Import\ParsedFile;
use DMF\Import\Template\ColumnMapping;
use DMF\Import\Template\MappingResult;

/**
 * Reshapes a ParsedFile's positional rows into rows keyed by canonical field
 * name, per a ColumnMapping. Pure mechanism — does not judge whether an
 * unmapped header or a missing required field is an error; that is
 * ValidatorInterface's job.
 *
 * Implemented by ColumnMapper today; any future mapping strategy (e.g. a
 * position-based, header-less mapping for a fixed-column legacy export)
 * implements the same contract.
 */
interface MappingInterface
{
    public function map(ParsedFile $file, ColumnMapping $mapping): MappingResult;
}
