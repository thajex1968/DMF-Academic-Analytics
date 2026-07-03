<?php

declare(strict_types=1);

namespace DMF\Action\Dashboard;

use DMF\Repository\ImportJobRepository;
use DMF\Repository\SchoolRepository;
use Dmf\Core\Auth\Guard;
use Dmf\Core\Config\Config;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;

/**
 * `GET action=dashboard_summary` (FR-002, decisions/IDR-010) — the
 * role-scoped dashboard shell's initial data, no analytics charts yet
 * (Phase 3). `StaffAuthMiddleware` already gates this route (any
 * authenticated principal); re-verifying the token here is cheap,
 * pure-CPU work (see IDR-010 §5) and keeps this class independently
 * correct even if ever wired without the middleware.
 *
 * Reports only what genuinely exists — no fabricated metrics.
 * "System Status" is deliberately minimal (PHP version, timezone, a
 * database reachability flag inferred from this request having already
 * queried the database) since no monitoring infrastructure exists yet.
 */
final class DashboardSummaryAction
{
    public function __construct(
        private readonly Guard $guard,
        private readonly Config $config,
        private readonly SchoolRepository $schools,
        private readonly ImportJobRepository $importJobs,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $principal = $this->guard->user($request->bearerToken());
        $schoolId = (int) $principal->claim('school_id', 0);

        $school = $schoolId > 0 ? $this->schools->findById($schoolId) : null;
        $jobs = $schoolId > 0 ? $this->importJobs->findBySchool($schoolId) : [];

        /** @var array<string, mixed> $appConfig */
        $appConfig = (array) $this->config->get('app', []);

        return Response::ok([
            'app' => [
                'name' => (string) ($appConfig['name'] ?? ''),
                'version' => (string) ($appConfig['version'] ?? ''),
                'env' => (string) ($appConfig['env'] ?? ''),
                'debug' => (bool) ($appConfig['debug'] ?? false),
            ],
            'user' => [
                'username' => (string) $principal->claim('username', ''),
                'display_name' => (string) $principal->claim('display_name', ''),
                'role' => $principal->role,
            ],
            'school' => [
                'id' => $schoolId,
                'name' => $school !== null ? (string) $school['name_th'] : null,
            ],
            'import_statistics' => $this->importStatistics($jobs),
            'recent_import_jobs' => $this->recentImportJobs($jobs),
            'system_status' => [
                'database' => 'ok',
                'php_version' => PHP_VERSION,
                'timezone' => date_default_timezone_get(),
            ],
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @return array<string, int>
     */
    private function importStatistics(array $jobs): array
    {
        $countByStatus = static function (array $jobs, string $status): int {
            return count(array_filter(
                $jobs,
                static fn (array $job): bool => $job['status'] === $status,
            ));
        };

        return [
            'total' => count($jobs),
            'queued' => $countByStatus($jobs, 'queued'),
            'processing' => $countByStatus($jobs, 'processing'),
            'committed' => $countByStatus($jobs, 'committed'),
            'failed' => $countByStatus($jobs, 'failed'),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @return array<int, array<string, mixed>>
     */
    private function recentImportJobs(array $jobs): array
    {
        return array_map(
            static fn (array $job): array => [
                'id' => (int) $job['id'],
                'status' => (string) $job['status'],
                'file_type' => (string) $job['file_type'],
                'created_at' => (string) $job['created_at'],
            ],
            array_slice($jobs, 0, 5),
        );
    }
}
