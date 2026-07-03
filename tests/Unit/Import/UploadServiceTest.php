<?php

declare(strict_types=1);

namespace DMF\Tests\Unit\Import;

use DMF\Import\FileValidationService;
use DMF\Import\ImportJobManager;
use DMF\Import\UploadService;
use DMF\Repository\ImportJobRepository;
use DMF\Repository\ImportLogRepository;
use Dmf\Core\Contract\ConnectionInterface;
use Dmf\Core\Exception\ValidationException;
use PDO;
use PDOStatement;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\TestCase;

/**
 * ImportJobManager is `final` and cannot be mocked directly — these tests
 * build a real ImportJobManager (wrapping real, `final` repositories) over a
 * mocked ConnectionInterface, one level deeper, and assert on the SQL/params
 * the manager ends up issuing.
 */
final class UploadServiceTest extends TestCase
{
    private string $storageDir;

    /** @var list<string> */
    private array $tempFiles = [];

    /** @var list<array{0: string, 1: array<int, mixed>}> */
    private array $executedQueries = [];

    protected function setUp(): void
    {
        $this->storageDir = sys_get_temp_dir() . '/dlap-storage-' . bin2hex(random_bytes(4));
        mkdir($this->storageDir);
    }

    #[After]
    public function cleanUp(): void
    {
        foreach ($this->tempFiles as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->tempFiles = [];

        foreach (glob($this->storageDir . '/*') ?: [] as $staged) {
            unlink($staged);
        }
        rmdir($this->storageDir);
    }

    public function testUploadStagesTheFileAndRegistersAQueuedJob(): void
    {
        $source = $this->makeSourceFile("student_id,score\nS001,87.5\n");

        $service = new UploadService(new FileValidationService(), $this->makeJobManager(), $this->storageDir);

        $jobId = $service->upload($source, 'scores.csv', filesize($source) ?: 0, 1, 2, 3);

        self::assertSame(10, $jobId);
        self::assertFileDoesNotExist($source, 'the source file should have been moved, not copied');

        [$jobSql, $jobParams] = $this->executedQueries[0];
        self::assertStringContainsString('INSERT INTO import_jobs', $jobSql);
        self::assertSame(1, $jobParams[0]);
        self::assertSame(2, $jobParams[1]);
        self::assertStringStartsWith($this->storageDir, $jobParams[2]);
        self::assertFileExists($jobParams[2]);
        self::assertSame('csv', $jobParams[3]);
        self::assertSame('queued', $jobParams[4]);
        self::assertSame(3, $jobParams[5]);
    }

    public function testUploadSanitizesTheOriginalFilenameInTheStagingPath(): void
    {
        $source = $this->makeSourceFile("a,b\n1,2\n");

        $service = new UploadService(new FileValidationService(), $this->makeJobManager(), $this->storageDir);

        $service->upload($source, '../../evil name!.csv', filesize($source) ?: 0, 1, 1, 1);

        [, $jobParams] = $this->executedQueries[0];
        $stagedPath = $jobParams[2];

        self::assertStringNotContainsString('..', $stagedPath);
        self::assertStringNotContainsString('/', basename($stagedPath));
    }

    public function testUploadThrowsAndDoesNotCreateAJobWhenValidationFails(): void
    {
        $source = $this->makeSourceFile('not a real spreadsheet', '.xlsx');

        $service = new UploadService(new FileValidationService(), $this->makeJobManager(), $this->storageDir);

        $this->expectException(ValidationException::class);

        try {
            $service->upload($source, 'scores.xlsx', filesize($source) ?: 0, 1, 1, 1);
        } finally {
            self::assertFileExists($source, 'a rejected file must not be moved out of its source location');
            self::assertSame([], $this->executedQueries, 'no import_jobs/import_logs row should be written');
        }
    }

    private function makeJobManager(): ImportJobManager
    {
        $this->executedQueries = [];
        $queries = &$this->executedQueries;

        $pdo = $this->createMock(PDO::class);
        $pdo->method('lastInsertId')->willReturn('10');

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->method('pdo')->willReturn($pdo);
        $connection->method('execute')->willReturnCallback(
            function (string $sql, array $params = []) use (&$queries): PDOStatement {
                $queries[] = [$sql, $params];

                return $this->createMock(PDOStatement::class);
            },
        );

        return new ImportJobManager(new ImportJobRepository($connection), new ImportLogRepository($connection));
    }

    private function makeSourceFile(string $contents, string $extension = '.csv'): string
    {
        $path = sys_get_temp_dir() . '/dlap-upload-source-' . bin2hex(random_bytes(4)) . $extension;
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
