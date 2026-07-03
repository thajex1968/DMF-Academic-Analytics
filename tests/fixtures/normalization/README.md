# Normalization Golden Dataset (T2.5)

Entirely synthetic, test-only curriculum catalogue + imported response rows — **not** a real
ตัวชี้วัด curriculum extract, same discipline already applied to T1.4's seeder and T2.2/T2.3's
example templates (no fabricated real-world curriculum content is ever committed as if it were
real). Built by `NormalizationFixtures.php`, a plain PHP data class (no file generation needed
here — unlike T2.3's `.xlsx`/`.csv` fixtures, `ItemIndicatorNormalizer`'s input is already an
in-memory array, not a parsed file).

## Catalogue shape

```
Strand ค1 (id 1) ─── Standard ค1.1 (id 1) ─┬─ Indicator id 1 (ค1.1 ป.6/1)
                                            └─ Indicator id 2 (ค1.1 ป.6/2)
Strand ค2 (id 2) ─── Standard ค2.1 (id 2) ─── Indicator id 3 (ค2.1 ป.6/1)
```

## Questions

| Question id | Primary indicator | Secondary indicator(s) | Scenario |
|---|---|---|---|
| 101 | 1 | — | Only a primary indicator |
| 102 | 1 | 2 (same standard/strand as primary) | Primary + secondary indicators |
| 103 | 1 | 3 (**different** standard/strand) | Question mapped to multiple standards |
| 104 | 999 (does not exist) | — | Missing / unresolvable indicator mapping |
| 105 | 1 | 1 (same as primary) | Duplicate indicator protection |

## Response rows (`NormalizationFixtures::responses()`)

| Row | student_id | question_id | Scenario |
|---|---|---|---|
| 1 | S001 | 101 | Only primary indicator |
| 2 | S001 | 102 | Primary + secondary |
| 3 | S002 | 103 | Multiple standards |
| 4 | S003 | 104 | Missing mapping → unresolved |
| 5 | S001 | 105 | Duplicate indicator protection |
| 6 | S002 | `null` | Invalid question_id → unresolved |

An empty response set (`[]`) and a response referencing a question id that has no `questions` row
at all (invalid question id, as opposed to a question with a broken indicator link) are covered
directly in `ItemIndicatorNormalizerTest` without needing a dedicated fixture row.
