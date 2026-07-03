<?php

declare(strict_types=1);

namespace DMF\Import\Template;

/**
 * A single, deliberately minimal example template, demonstrating how a real
 * one (e.g. "ONET-2569") would be built and registered.
 *
 * This is NOT a production-ready ONET/NT/RT/School Assessment template. It
 * uses only the one header-alias pair FR-004 actually documents
 * ("รหัสนักเรียน" / "เลขประจำตัว" → student_id). The real required/optional
 * column list and validation rules for each academic year's official สทศ
 * file must come from the real file specification (the "per-academic-year
 * import template registry" docs/02-System-Architecture.md §7 describes) —
 * fabricating one here would be inventing data a real teacher/director's
 * import depends on being correct, the same category of risk flagged and
 * declined for the standards catalogue during T1.4 (Seeder).
 */
final class ExampleTemplates
{
    private function __construct()
    {
        // Static-only factory; never instantiated.
    }

    public static function studentIdOnly(): ImportTemplate
    {
        return new ImportTemplate(
            key: 'EXAMPLE-STUDENT-ID-ONLY',
            mappingVersion: 'v1',
            mapping: new ColumnMapping([
                'student_id' => ['รหัสนักเรียน', 'เลขประจำตัว'],
            ]),
            requiredColumns: ['student_id'],
            optionalColumns: [],
            validationRules: [
                // Matches Dmf\Core\Validation\Validator's own documented example exactly.
                'student_id' => 'required|max:20',
            ],
        );
    }

    /**
     * A second, still deliberately minimal example, adding a "score" column
     * — needed to exercise the Score Import Pipeline (Task T2.3) and its
     * Golden Test Dataset (tests/fixtures/import/). The header text "score"
     * is a plain, generic placeholder, not a documented real สทศ header —
     * same disclaimer as studentIdOnly() above applies. The score *range*
     * (0.00–100.00) is enforced by ScoreNormalizer with real float
     * comparison, not by this template's validationRules — see
     * ScoreNormalizer's own docblock for why `int_range` is not used here.
     */
    public static function studentIdAndScore(): ImportTemplate
    {
        return new ImportTemplate(
            key: 'EXAMPLE-STUDENT-ID-AND-SCORE',
            mappingVersion: 'v1',
            mapping: new ColumnMapping([
                'student_id' => ['รหัสนักเรียน', 'เลขประจำตัว'],
                'score' => ['score', 'คะแนน'],
            ]),
            requiredColumns: ['student_id', 'score'],
            optionalColumns: [],
            validationRules: [
                'student_id' => 'required|max:20',
                'score' => 'required',
            ],
        );
    }
}
