# IDR-010 — Web Application Foundation: SPA Architecture, Auth Design, Scope Mapping

**Status:** Accepted — 2026-07-03
**Implements:** [decisions/IDR-005](IDR-005-database-connection-strategy.md),
[decisions/IDR-009](IDR-009-cron-import-runner.md) (`ConnectionFactory` reused here); Tasks T1.6/T1.7
([IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md#2-task)) — staff authentication (FR-001) and
the role-scoped dashboard shell (FR-002).

## Context

The Sprint 3 instruction that triggered this work described a classic server-rendered multi-page
app: PHP sessions, CSRF tokens, session fixation prevention, and five page files
(`login.php`/`logout.php`/`dashboard.php`/`403.php`/`404.php`) sharing a header/sidebar/footer
layout. Before writing any code, this was checked against what is actually documented and already
built, and a real conflict was found:

* [docs/02-System-Architecture.md §5/§6](../docs/02-System-Architecture.md#5-repository--directory-structure)
  (frozen) specifies a **single-page app**: `public_html/index.html` (Bootstrap 5 + Chart.js shell)
  talking to **one** JSON front controller (`public_html/api/index.php`) via `dmf-core`'s
  `Http\Router`, on `"METHOD:action"` dispatch — not multiple server-rendered PHP pages.
* FR-001 itself says authentication must match "the pattern already in production at
  `grade.dmf.ac.th`" — confirmed by reading `grade.dmf.ac.th`'s actual code: a Bearer token held
  client-side in `sessionStorage`, no PHP session, no cookie, CSRF used exactly once anywhere in
  that whole codebase (a one-time `install.php` wizard, unrelated to the API/SPA auth flow).
* `Dmf\Core\Http\Response::send()` is hardcoded to `Content-Type: application/json` — it has no HTML
  rendering capability, so it cannot serve `login.php`-style pages as the instruction described.
* `docs/Naming-Convention.md §3` documents `?action=` `snake_case` dispatch as "the *only* routing
  mechanism v1.0 actually uses" — there is no path-based multi-page routing convention.

Per `START_SESSION.md`'s rule — "if implementing a task reveals the architecture doesn't fit, that
is a new RFC or ADR, not an in-place change" — this was raised with you before writing any code
rather than silently building either interpretation. **You chose to follow the documented SPA
architecture.** This IDR records that decision plus every concrete implementation choice it forced.

## Decision

### 1. Architecture: SPA + JSON API + Bearer token (no deviation, no ADR needed)

`public_html/index.html` (Bootstrap 5 via CDN, vanilla JS, no build step — matching this project's
already-documented frontend stack and `grade.dmf.ac.th`'s no-build-step precedent) is the single
HTML entry point. `public_html/api/index.php` is the single JSON front controller. The token is
issued once at login and held in the browser's `sessionStorage`; "logout" is primarily a client-side
action (discarding the token) — see §5 below for why server-side revocation is necessarily
best-effort for a stateless HMAC token, exactly as `dmf-core`'s own `TokenManager::revoke()`
docblock already says.

### 2. Class-name mapping: the literal request named `AuthenticationService`, `SessionManager`,
`AuthMiddleware`

Those names came from the session-based mental model this IDR's §Context section already rejected.
Mapped onto the actual, already-decided `dmf-core` Auth module (Coding Rule 2 — depend on
`dmf-core` contracts, never reimplement):

| Requested | Built as | Why |
|---|---|---|
| `AuthenticationService` | `DMF\Auth\StaffGuard extends Guard` + `DMF\Auth\StaffTokenManager extends TokenManager` | `dmf-core`'s `Guard`/`TokenManager` pair *is* the authentication service — `Guard::login()` orchestrates throttling + credential verification + token issuance in one call. |
| `SessionManager` | *(not built — architecturally does not apply)* | There is no server-side session to manage; the token's lifecycle (issue, verify, expire) is `TokenManager`'s job, already covered above. Building a `SessionManager` class with nothing to manage would be inventing an unused abstraction. |
| `AuthMiddleware` | `DMF\Http\Middleware\StaffAuthMiddleware extends Dmf\Core\Http\Middleware\AuthMiddleware` | Maps directly — `dmf-core` already provides the abstract gate; this task only needed a concrete subclass. |

### 3. `StaffRateLimiter` — MySQL-backed, not session-backed

`grade.dmf.ac.th`'s `SessionRateLimiter` (the only existing concrete `RateLimiter` in the platform)
stores attempts in `$_SESSION`. T1.6 explicitly requires `login_rate_limits` (a real table, already
in `database/schema.sql`) as the backing store — matching this module's stated no-session
architecture and `docs/03-Database-Design.md`'s own description of that table ("backing store for
`dmf-core`'s `Auth\RateLimiter` on shared hosting, no Redis/APCu assumed"). `DMF\Auth\StaffRateLimiter`
implements the four abstract primitives (`increment`/`lockUntil`/`getLockExpiry`/`clear`) against a
new `DMF\Repository\LoginRateLimitRepository`, keyed on the plain `username` column (no prefix — the
column is literally named `username` and no other principal type shares this table in v1.0, so a
`"staff:"`-style scoping prefix like `grade.dmf.ac.th`'s session keys use would only add noise).

### 4. Timing-safe login: always run the bcrypt comparison

`StaffTokenManager::authenticate()` calls `PasswordHasher::verify()` unconditionally — against a
fixed dummy bcrypt hash when the username doesn't resolve to a real row — so a request for a
nonexistent username and a request for a real username with a wrong password take approximately the
same amount of server time. `grade.dmf.ac.th`'s equivalent code does not do this (it returns
immediately on `!$u`); this is a small, genuine security improvement made while building the same
category of code, not a requirement literally stated anywhere in `docs/`.

### 5. No principal registry — the dashboard handler re-verifies its own token

`AuthMiddleware::withPrincipal()`'s default implementation returns the `Request` unchanged, and
`Dmf\Core\Http\Request` is `final` — it cannot be extended to carry a `Principal`, and the docblock's
suggested alternative is "use a registry" (i.e., some mutable global/service-locator holding the
current request's principal). A registry was **rejected**: it reintroduces global mutable state into
an otherwise stateless-token system for no real benefit here, since `Guard::user()` is pure CPU work
(HMAC verify + JSON decode, no I/O) — cheap enough that `DashboardSummaryAction` simply calls
`$guard->user($request->bearerToken())` itself. `StaffAuthMiddleware` still runs first in the
composed pipeline and still provides the actual security gate (unauthenticated/wrong-role requests
never reach the handler); the handler's second call is redundant-but-cheap defense in depth, not the
primary check.

### 6. No `Gate`/`Policy` layer built this pass

`dmf-core`'s `Authorization\Gate`/`Policy`/`Role` classes exist and are usable, but Sprint 3's actual
scope — "support roles... unauthorized users get 403" — is already fully satisfied by
`AuthMiddleware`'s existing `$requiredRole` mechanism (`StaffAuthMiddleware`'s constructor accepts an
optional required role, so a future admin-only route is `new StaffAuthMiddleware($guard,
'admin')` with no new class). Building a parallel `Gate`/`Policy` layer for an authorization decision
this simple would be speculative (YAGNI,
[Architecture-Principles.md §7](../docs/Architecture-Principles.md#7-yagni--you-arent-gonna-need-it))
— FR-002's real scoping rule ("a teacher's queries are always constrained server-side to their
assigned classroom(s)") has no classroom-linked dashboard data to scope yet (Sprint 3 is explicitly
"no Analytics"), so there is nothing for a `Policy` to check beyond "is this principal authenticated
(and, later, does it hold the required role)" — exactly what the middleware already does.

### 7. Namespace layout

`DMF\Auth\{StaffTokenManager,StaffRateLimiter,StaffGuard}` (mirrors `grade.dmf.ac.th`'s actual
`app/Auth/*` layout — the *real* precedent this project has followed for every namespace so far,
e.g. `DMF\Import\*`, `DMF\Analytics\*`, `DMF\Database\*`, none of which used the illustrative
`app/Action/*` tree `docs/02-System-Architecture.md §5` sketches). `DMF\Http\Middleware\StaffAuthMiddleware`
mirrors `dmf-core`'s own `Http\Middleware` sub-namespace. `DMF\Action\Auth\{LoginStaffAction,LogoutStaffAction}`
and `DMF\Action\Dashboard\DashboardSummaryAction` **do** follow the documented `app/Action/*` tree,
since these thin, one-class-per-route HTTP handlers are exactly what that tree names — the two
conventions are not actually in conflict, they answer different questions (where do the
`dmf-core`-subclass *services* live, vs. where do the *route handlers* that use them live).

### 8. Action naming

`login_staff`, `logout_staff`, `dashboard_summary` — `snake_case`, matching
`docs/Naming-Convention.md §3`'s documented vocabulary style (`login_student`, `class_summary`) and
`grade.dmf.ac.th`'s existing `login_staff`-shaped actions.

## Alternatives Considered

* **Server-rendered multi-page app with PHP sessions/CSRF**, per the instruction's literal wording —
  rejected per your explicit direction; would have required a new ADR (contradicts frozen
  `02-System-Architecture.md`) and a parallel HTML-rendering layer `dmf-core`'s `Http\Response`
  doesn't provide.
* **Hybrid (real PHP page files that internally call the JSON API)** — considered as a middle
  option in the same question that resolved this; not chosen. Would have required inventing new
  page-file/layout-partial naming conventions with no precedent in `docs/` or `grade.dmf.ac.th`.
* **A principal registry (static holder class) so `AuthMiddleware::withPrincipal()` has something
  concrete to attach** — rejected, see §5 above.
* **Building `Gate`/`Policy` now "for the future"** — rejected as premature; `$requiredRole` already
  covers the stated need, see §6.

## Consequences

* `public_html/api/index.php` becomes the second file ever created under `public_html/` (the first
  was T2.7's cron entry point) — both plain procedural scripts, consistent with this project's only
  established `public_html/` convention so far.
* A future task that needs true per-classroom/per-resource authorization (FR-002's deferred scoping
  rule) will introduce `Gate`/`Policy` then, once there is real classroom-linked data to scope
  against — not before.
* If a future token-revocation requirement appears (e.g., "admin can force-logout a compromised
  account immediately"), `TokenManager::revoke()`'s docblock already names the extension point (a
  denylist store) — not built here, since nothing in T1.6/T1.7 asks for it.
