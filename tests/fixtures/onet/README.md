# O-NET Example Fixtures (placeholder)

This directory is the designated location for **sanitized, synthetic, test-only** O-NET-shaped
fixtures — for whoever needs example data without access to (or a need for) the real reference
dataset. It is deliberately empty right now: no fixture has been fabricated here on spec, matching
the same discipline already applied to T1.4's seeder, T2.2/T2.3's example templates, and T2.5's
`NormalizationFixtures.php` (no invented real-world curriculum or score content is ever committed
as if it were real).

**Not to be confused with `Onet/`** (repo root, gitignored) — that directory holds the actual,
real, primary-source O-NET PDF/XLSX/CSV score reports for this school, referenced directly by
[RFC-004](../../../docs/rfcs/RFC-004-multi-source-analytics-architecture.md)'s Evidence section.
It stays local only and is never committed; this directory is its safe, committable, synthetic
counterpart, populated only when a concrete task actually needs example O-NET fixtures — see
`tests/fixtures/normalization/README.md` for the pattern to follow when that happens.
