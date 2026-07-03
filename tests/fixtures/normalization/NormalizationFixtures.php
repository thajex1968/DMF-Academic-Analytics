<?php

declare(strict_types=1);

namespace DMF\Tests\Fixtures\Normalization;

/**
 * The Golden Dataset for T2.5 (Item-to-Indicator Normalization): one small,
 * internally-consistent curriculum catalogue (strands → standards →
 * indicators → questions) plus a set of imported response rows, each
 * engineered to exercise one normalization scenario. See README.md in this
 * directory for the scenario-to-row mapping.
 *
 * Entirely synthetic, test-only data — not sourced from the real ตัวชี้วัด
 * curriculum catalogue, same discipline already applied to T1.4's seeder and
 * T2.2/T2.3's example templates (no fabricated real-world curriculum
 * content is ever committed as if it were real).
 */
final class NormalizationFixtures
{
    /** @return array<int, array<string, mixed>> Keyed by id via array index for lookup convenience. */
    public static function strands(): array
    {
        return [
            1 => [
                'id' => 1, 'subject_code' => 'MATH', 'strand_code' => 'ค1',
                'strand_name_th' => 'จำนวนและพีชคณิต (ทดสอบ)',
            ],
            2 => [
                'id' => 2, 'subject_code' => 'MATH', 'strand_code' => 'ค2',
                'strand_name_th' => 'การวัดและเรขาคณิต (ทดสอบ)',
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function standards(): array
    {
        return [
            1 => ['id' => 1, 'strand_id' => 1, 'standard_code' => 'ค1.1', 'standard_name_th' => 'มาตรฐานทดสอบ 1'],
            2 => ['id' => 2, 'strand_id' => 2, 'standard_code' => 'ค2.1', 'standard_name_th' => 'มาตรฐานทดสอบ 2'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function indicators(): array
    {
        return [
            1 => [
                'id' => 1, 'standard_id' => 1, 'indicator_code' => 'ค1.1 ป.6/1',
                'indicator_name_th' => 'ตัวชี้วัดทดสอบ 1', 'grade_level' => 6, 'curriculum_revision' => '2560',
            ],
            2 => [
                'id' => 2, 'standard_id' => 1, 'indicator_code' => 'ค1.1 ป.6/2',
                'indicator_name_th' => 'ตัวชี้วัดทดสอบ 2', 'grade_level' => 6, 'curriculum_revision' => '2560',
            ],
            3 => [
                'id' => 3, 'standard_id' => 2, 'indicator_code' => 'ค2.1 ป.6/1',
                'indicator_name_th' => 'ตัวชี้วัดทดสอบ 3', 'grade_level' => 6, 'curriculum_revision' => '2560',
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function questions(): array
    {
        return [
            // Only a primary indicator (no secondary links).
            101 => [
                'id' => 101, 'assessment_id' => 3, 'item_number' => 1,
                'primary_indicator_id' => 1, 'correct_choice' => '1',
            ],
            // Primary + one secondary indicator, same standard/strand as the primary.
            102 => [
                'id' => 102, 'assessment_id' => 3, 'item_number' => 2,
                'primary_indicator_id' => 1, 'correct_choice' => '2',
            ],
            // Primary + one secondary indicator from a *different* standard/strand.
            103 => [
                'id' => 103, 'assessment_id' => 3, 'item_number' => 3,
                'primary_indicator_id' => 1, 'correct_choice' => '3',
            ],
            // Primary indicator id (999) does not exist in the catalogue — unresolvable.
            104 => [
                'id' => 104, 'assessment_id' => 3, 'item_number' => 4,
                'primary_indicator_id' => 999, 'correct_choice' => '4',
            ],
            // Secondary link points at the same indicator as the primary — duplicate indicator protection.
            105 => [
                'id' => 105, 'assessment_id' => 3, 'item_number' => 5,
                'primary_indicator_id' => 1, 'correct_choice' => '1',
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> question_secondary_indicators rows. */
    public static function secondaryIndicatorLinks(): array
    {
        return [
            ['id' => 1, 'question_id' => 102, 'indicator_id' => 2],
            ['id' => 2, 'question_id' => 103, 'indicator_id' => 3],
            ['id' => 3, 'question_id' => 105, 'indicator_id' => 1],
        ];
    }

    /**
     * Imported student_question_responses rows — the input ItemIndicatorNormalizer
     * iterates. One row per scenario named in the comment.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function responses(): array
    {
        return [
            // Row 1: question with only a primary indicator.
            ['student_id' => 'S001', 'question_id' => 101, 'selected_choice' => '1', 'is_correct' => true],
            // Row 2: question with primary + secondary indicators.
            ['student_id' => 'S001', 'question_id' => 102, 'selected_choice' => '3', 'is_correct' => false],
            // Row 3: question mapped to multiple standards (primary and secondary in different strands).
            ['student_id' => 'S002', 'question_id' => 103, 'selected_choice' => '3', 'is_correct' => true],
            // Row 4: question with a missing/unresolvable indicator mapping.
            ['student_id' => 'S003', 'question_id' => 104, 'selected_choice' => '4', 'is_correct' => true],
            // Row 5: duplicate indicator protection (secondary === primary indicator).
            ['student_id' => 'S001', 'question_id' => 105, 'selected_choice' => '1', 'is_correct' => true],
            // Row 6: invalid question_id.
            ['student_id' => 'S002', 'question_id' => null, 'selected_choice' => '1', 'is_correct' => false],
        ];
    }
}
