<?php

declare(strict_types=1);

/**
 * The single JSON front controller for onet.dmf.ac.th (T1.6/T1.7,
 * decisions/IDR-010) — dispatches every `?action=` request via
 * `dmf-core`'s `Http\Router`, matching docs/02-System-Architecture.md
 * §5/§6 and `grade.dmf.ac.th`'s reference pattern exactly. Registers only
 * the three routes this sprint builds: `login_staff`, `logout_staff`,
 * `dashboard_summary` — every other `?action=` value falls through to the
 * Router's own `Response::notFound()`.
 */

use DMF\Action\Auth\LoginStaffAction;
use DMF\Action\Auth\LogoutStaffAction;
use DMF\Action\Dashboard\DashboardAssessmentAction;
use DMF\Action\Dashboard\DashboardBenchmarkAction;
use DMF\Action\Dashboard\DashboardHealthAction;
use DMF\Action\Dashboard\DashboardOverviewAction;
use DMF\Action\Dashboard\DashboardSubjectAction;
use DMF\Action\Dashboard\DashboardSummaryAction;
use DMF\Analytics\Aggregation\AnalyticsAggregationService;
use DMF\Analytics\Aggregation\AssessmentSummaryAggregator;
use DMF\Analytics\Aggregation\BenchmarkAggregator;
use DMF\Analytics\Aggregation\DashboardHealthAggregator;
use DMF\Analytics\Aggregation\StandardSummaryAggregator;
use DMF\Analytics\Aggregation\StrandSummaryAggregator;
use DMF\Analytics\Aggregation\SubjectSummaryAggregator;
use DMF\Analytics\Calculators\BenchmarkCalculator;
use DMF\Analytics\Calculators\DifficultyCalculator;
use DMF\Analytics\Calculators\StandardPerformanceCalculator;
use DMF\Analytics\Calculators\StrandPerformanceCalculator;
use DMF\Analytics\Calculators\SubjectPerformanceCalculator;
use DMF\Analytics\Cache\InMemoryDashboardCache;
use DMF\Analytics\Context\AnalyticsContextFactory;
use DMF\Analytics\Dashboard\DashboardResponseSerializer;
use DMF\Analytics\Normalization\ItemIndicatorNormalizer;
use DMF\Analytics\Normalization\QuestionStandardResolver;
use DMF\Analytics\Normalization\StandardMappingService;
use DMF\Analytics\Pipeline\AnalyticsPipeline;
use DMF\Auth\StaffGuard;
use DMF\Auth\StaffRateLimiter;
use DMF\Auth\StaffTokenManager;
use DMF\Database\ConnectionFactory;
use DMF\Http\Middleware\StaffAuthMiddleware;
use DMF\Repository\AnalyticsReadRepository;
use DMF\Repository\AssessmentRepository;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\LearningIndicatorRepository;
use DMF\Repository\LearningStandardRepository;
use DMF\Repository\LearningStrandRepository;
use DMF\Repository\LoginRateLimitRepository;
use DMF\Repository\QuestionRepository;
use DMF\Repository\QuestionSecondaryIndicatorRepository;
use DMF\Repository\SchoolRepository;
use DMF\Repository\StaffUserRepository;
use DMF\Repository\StudentRepository;
use Dmf\Core\Http\Request;
use Dmf\Core\Http\Response;
use Dmf\Core\Http\Router;
use Dmf\Core\Security\PasswordHasher;

$config = require dirname(__DIR__, 2) . '/bootstrap/app.php';

// Security headers — docs/02-System-Architecture.md §14.
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');

$connection = ConnectionFactory::fromConfig($config);

/** @var array<string, mixed> $authConfig */
$authConfig = (array) $config->get('auth', []);

$tokens = new StaffTokenManager(
    (string) ($authConfig['token_secret'] ?? ''),
    (int) ($authConfig['token_ttl'] ?? 28800),
    new StaffUserRepository($connection),
    new PasswordHasher(),
);

$limiter = new StaffRateLimiter(
    new LoginRateLimitRepository($connection),
    (int) ($authConfig['max_login_fail'] ?? 5),
    (int) ($authConfig['lockout_seconds'] ?? 300),
);

$guard = new StaffGuard($tokens, $limiter);
$requireAuth = new StaffAuthMiddleware($guard);

$router = new Router();

$router->add('POST', 'login_staff', new LoginStaffAction($guard));
$router->add('POST', 'logout_staff', new LogoutStaffAction($guard));

$dashboardSummary = new DashboardSummaryAction(
    $guard,
    $config,
    new SchoolRepository($connection),
    new ImportJobRepository($connection),
);
$router->add(
    'GET',
    'dashboard_summary',
    static fn (Request $request): Response => $requireAuth->handle($request, $dashboardSummary),
);

// Sprint 4 Phase 3 (decisions/IDR-011) — Analytics Aggregation + Dashboard Data API. Reuses
// Normalization (T2.5), the Canonical Analytics Model + Pipeline (Sprint 4 Phase 1), and every
// calculator (Sprint 4 Phase 2) unchanged; the only new repository is AnalyticsReadRepository.
$questionStandardResolver = new QuestionStandardResolver(
    new QuestionRepository($connection),
    new QuestionSecondaryIndicatorRepository($connection),
    new LearningIndicatorRepository($connection),
    new LearningStandardRepository($connection),
    new LearningStrandRepository($connection),
);
$normalizer = new ItemIndicatorNormalizer(new StandardMappingService($questionStandardResolver));

$pipeline = new AnalyticsPipeline([
    new DifficultyCalculator(),
    new BenchmarkCalculator(),
    new StandardPerformanceCalculator(),
    new SubjectPerformanceCalculator(),
    new StrandPerformanceCalculator(),
]);

$assessmentRepository = new AssessmentRepository($connection);

$aggregationService = new AnalyticsAggregationService(
    $assessmentRepository,
    new AnalyticsReadRepository($connection),
    $normalizer,
    new AnalyticsContextFactory(),
    $pipeline,
    new AssessmentSummaryAggregator(),
    new SubjectSummaryAggregator(),
    new StrandSummaryAggregator(),
    new StandardSummaryAggregator(),
    new BenchmarkAggregator(),
    new InMemoryDashboardCache(),
);

$dashboardSerializer = new DashboardResponseSerializer();

$dashboardOverview = new DashboardOverviewAction($aggregationService, $dashboardSerializer);
$router->add(
    'GET',
    'dashboard_overview',
    static fn (Request $request): Response => $requireAuth->handle($request, $dashboardOverview),
);

$dashboardAssessment = new DashboardAssessmentAction($aggregationService, $dashboardSerializer);
$router->add(
    'GET',
    'dashboard_assessment',
    static fn (Request $request): Response => $requireAuth->handle($request, $dashboardAssessment),
);

$dashboardSubjects = new DashboardSubjectAction($aggregationService, $dashboardSerializer);
$router->add(
    'GET',
    'dashboard_subjects',
    static fn (Request $request): Response => $requireAuth->handle($request, $dashboardSubjects),
);

$dashboardBenchmark = new DashboardBenchmarkAction($aggregationService, $dashboardSerializer);
$router->add(
    'GET',
    'dashboard_benchmark',
    static fn (Request $request): Response => $requireAuth->handle($request, $dashboardBenchmark),
);

$dashboardHealthAggregator = new DashboardHealthAggregator(
    new ImportJobRepository($connection),
    $assessmentRepository,
    new StudentRepository($connection),
);
$dashboardHealth = new DashboardHealthAction($dashboardHealthAggregator, $dashboardSerializer);
$router->add(
    'GET',
    'dashboard_health',
    static fn (Request $request): Response => $requireAuth->handle($request, $dashboardHealth),
);

$response = $router->dispatch(Request::fromGlobals());

// dmf-core's Router::dispatch() puts any non-AuthException Throwable's raw
// ->getMessage() directly into a 500 response — for a DatabaseException that
// includes the driver's SQLSTATE detail (confirmed directly: a connection
// failure surfaces "SQLSTATE[HY000] [2002] ..." verbatim). That is
// dmf-core's own framework behavior, not something this project can change
// here, but exposing SQLSTATE/driver detail to the client contradicts this
// project's own security discipline (see decisions/IDR-008's audit-message
// safety rule). Replace only a 500's message, only when APP_DEBUG is off —
// debug mode still shows the real detail for local troubleshooting.
/** @var array<string, mixed> $appConfig */
$appConfig = (array) $config->get('app', []);

if ($response->statusCode() === 500 && !(bool) ($appConfig['debug'] ?? false)) {
    $response = Response::serverError('An unexpected error occurred. Please try again later.');
}

$response->send();
