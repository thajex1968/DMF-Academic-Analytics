# IDR-001 — PhpSpreadsheet for Excel Import

**Status:** Accepted — 2026-07-02
**Implements:** Task T2.1 ([IMPLEMENTATION_GUIDE.md §2](../IMPLEMENTATION_GUIDE.md#2-task)); no
prior ADR named a specific library — this is the first decision at this level of detail for the
Import module's `.xlsx` path.

## Context

PRD FR-004 requires reading official O-NET score exports supplied as `.xlsx` workbooks — resolving
header text (which may vary slightly year to year, e.g. "รหัสนักเรียน" vs. "เลขประจำตัว") to a
canonical student-ID column, and reading subject-score and, where present, item-response columns.
This must run inside the cron-driven import job
([docs/02-System-Architecture.md §7](../docs/02-System-Architecture.md#7-import-pipeline-architecture)),
on shared DirectAdmin/cPanel hosting with no guarantee of anything beyond a standard PHP extension
set (`ext-pdo`, `ext-json`, `ext-hash`, plus whatever `ext-zip`/`ext-xml`/`ext-gd` the hosting
account's PHP build includes — these are common but not universal on shared plans).

## Decision

Use **PhpSpreadsheet** (`phpoffice/phpspreadsheet`) to parse `.xlsx` files in `DMF\Import\Parser\ExcelParser`.

## Alternatives Considered

* **Box/Spout** (`box/spout`, now community-maintained as `openspout/openspout`) — rejected.
  Faster and lower-memory for very large files, but with a smaller feature surface for reading
  existing formatting/merged-cell metadata, which matters here because a per-academic-year import
  template ([docs/02-System-Architecture.md
  §7](../docs/02-System-Architecture.md#7-import-pipeline-architecture)) sometimes needs to
  distinguish a header row by its formatting, not just its text. PhpSpreadsheet's broader read
  capability is worth its extra memory overhead at this dataset's scale (a few hundred students'
  rows per file, not tens of thousands).
* **A native PHP `ext-xlsx`-style extension** — rejected. No such extension ships with standard
  PHP; anything in this category would require a shared-hosting provider to install a custom PHP
  extension, which contradicts the "no infrastructure the shared host doesn't already provide"
  constraint ([docs/Architecture-Principles.md
  §2](../docs/Architecture-Principles.md#2-convention-over-configuration)).
* **Shelling out to a Python `openpyxl`/`pandas` script** — rejected outright. Shared hosting has
  no guaranteed shell access beyond FTP/SSH file upload
  ([docs/02-System-Architecture.md §13](../docs/02-System-Architecture.md#13-deployment-architecture)),
  and this would violate the Modular Monolith decision
  ([ADR-001](../docs/Architecture-Decision-Record.md#adr-001--why-modular-monolith)) by introducing
  a second runtime the PHP process has to invoke and trust.

## Consequences

Adds `phpoffice/phpspreadsheet` as a production Composer dependency. It is a pure-PHP library (no
compiled extension beyond the standard `ext-zip`/`ext-xml` PHP already needs for `.xlsx`'s
underlying ZIP/XML format), consistent with the "no infrastructure the host doesn't provide"
constraint. Memory usage on large workbooks should be verified against the target hosting account's
PHP `memory_limit` during Task T2.1 — if a specific academic year's file proves too large for
inline (even cron-context) parsing, the mitigation is chunked/cell-iterator reading (a
PhpSpreadsheet-supported mode), not a library change.
