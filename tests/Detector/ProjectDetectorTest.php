<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Detector;

use Enabel\CodingStandard\Detector\ProjectDetector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

final class ProjectDetectorTest extends TestCase
{
    private string $tempDir;
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/project-detector-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testGetProjectNameFromComposerJson(): void
    {
        $this->writeComposerJson(['name' => 'enabel/my-project']);

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame('my-project', $detector->getProjectName());
    }

    public function testGetProjectNameReturnsNullWithoutComposerJson(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertNull($detector->getProjectName());
    }

    public function testGetPhpVersionFromConstraint(): void
    {
        $this->writeComposerJson(['require' => ['php' => '>=8.3']]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame('8.3', $detector->getPhpVersion());
    }

    public function testGetPhpVersionFromCaretConstraint(): void
    {
        $this->writeComposerJson(['require' => ['php' => '^8.4']]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame('8.4', $detector->getPhpVersion());
    }

    public function testGetPhpVersionReturnsNullForUnsupported(): void
    {
        $this->writeComposerJson(['require' => ['php' => '>=7.4']]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertNull($detector->getPhpVersion());
    }

    public function testIsSymfonyProjectReturnsTrueWhenFrameworkBundlePresent(): void
    {
        $this->writeComposerJson(['require' => ['symfony/framework-bundle' => '^7.4']]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertTrue($detector->isSymfonyProject());
    }

    public function testIsSymfonyProjectReturnsFalseWhenAbsent(): void
    {
        $this->writeComposerJson(['require' => ['some/package' => '^1.0']]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertFalse($detector->isSymfonyProject());
    }

    public function testIsSymfonyProjectReturnsFalseWithoutComposerJson(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertFalse($detector->isSymfonyProject());
    }

    public function testGetSymfonyVersionFromComposerLock(): void
    {
        $this->writeComposerJson(['require' => ['symfony/framework-bundle' => '^7.4']]);
        $this->writeComposerLock([
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v7.2.3'],
            ],
        ]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame('7.4', $detector->getSymfonyVersion());
    }

    public function testGetSymfonyVersionFromComposerLockV8(): void
    {
        $this->writeComposerJson(['require' => ['symfony/framework-bundle' => '^8.0']]);
        $this->writeComposerLock([
            'packages' => [
                ['name' => 'symfony/framework-bundle', 'version' => 'v8.0.1'],
            ],
        ]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame('8.0', $detector->getSymfonyVersion());
    }

    public function testGetSymfonyVersionFallsBackToComposerJsonConstraint(): void
    {
        $this->writeComposerJson(['require' => ['symfony/framework-bundle' => '^8.0']]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame('8.0', $detector->getSymfonyVersion());
    }

    public function testGetSymfonyVersionReturnsNullWhenNotSymfony(): void
    {
        $this->writeComposerJson(['require' => ['some/package' => '^1.0']]);

        $detector = new ProjectDetector($this->tempDir);

        self::assertNull($detector->getSymfonyVersion());
    }

    // --- CI Provider detection ---

    public function testDetectCiProvidersReturnsEmptyWhenNoCiFiles(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertSame([], $detector->detectCiProviders());
    }

    public function testDetectCiProvidersDetectsGitlab(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame(['gitlab'], $detector->detectCiProviders());
    }

    public function testDetectCiProvidersDetectsGithub(): void
    {
        $this->filesystem->mkdir($this->tempDir . '/.github/workflows');
        file_put_contents($this->tempDir . '/.github/workflows/ci.yml', 'name: CI');

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame(['github'], $detector->detectCiProviders());
    }

    public function testDetectCiProvidersDetectsAzure(): void
    {
        file_put_contents($this->tempDir . '/azure-pipelines.yml', 'trigger: [main]');

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame(['azure'], $detector->detectCiProviders());
    }

    public function testDetectCiProvidersDetectsMultiple(): void
    {
        file_put_contents($this->tempDir . '/.gitlab-ci.yml', 'stages: [test]');
        $this->filesystem->mkdir($this->tempDir . '/.github/workflows');
        file_put_contents($this->tempDir . '/.github/workflows/ci.yml', 'name: CI');

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame(['gitlab', 'github'], $detector->detectCiProviders());
    }

    // --- Database detection ---

    public function testDetectDatabaseReturnsNullWithoutComposeYaml(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertNull($detector->detectDatabase());
    }

    public function testDetectDatabaseDetectsMariadb(): void
    {
        file_put_contents($this->tempDir . '/compose.yaml', "services:\n  database:\n    image: mariadb:11.4\n");

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame(['type' => 'mariadb', 'version' => '11.4'], $detector->detectDatabase());
    }

    public function testDetectDatabaseDetectsMysql(): void
    {
        file_put_contents($this->tempDir . '/compose.yaml', "services:\n  database:\n    image: mysql:8.4\n");

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame(['type' => 'mysql', 'version' => '8.4'], $detector->detectDatabase());
    }

    public function testDetectDatabaseDetectsPostgresql(): void
    {
        file_put_contents($this->tempDir . '/compose.yaml', "services:\n  database:\n    image: postgres:17\n");

        $detector = new ProjectDetector($this->tempDir);

        self::assertSame(['type' => 'postgresql', 'version' => '17'], $detector->detectDatabase());
    }

    public function testDetectDatabaseReturnsNullWhenNoDbImage(): void
    {
        file_put_contents($this->tempDir . '/compose.yaml', "services:\n  app:\n    image: php:8.4\n");

        $detector = new ProjectDetector($this->tempDir);

        self::assertNull($detector->detectDatabase());
    }

    // --- Tool detection ---

    public function testHasPhpCsFixerReturnsTrueWhenFileExists(): void
    {
        file_put_contents($this->tempDir . '/.php-cs-fixer.dist.php', '<?php return [];');

        $detector = new ProjectDetector($this->tempDir);

        self::assertTrue($detector->hasPhpCsFixer());
    }

    public function testHasPhpCsFixerReturnsFalseWhenFileAbsent(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertFalse($detector->hasPhpCsFixer());
    }

    public function testHasPhpStanReturnsTrueWhenFileExists(): void
    {
        file_put_contents($this->tempDir . '/phpstan.neon', 'parameters:');

        $detector = new ProjectDetector($this->tempDir);

        self::assertTrue($detector->hasPhpStan());
    }

    public function testHasPhpStanReturnsFalseWhenFileAbsent(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertFalse($detector->hasPhpStan());
    }

    public function testHasRectorReturnsTrueWhenFileExists(): void
    {
        file_put_contents($this->tempDir . '/rector.php', '<?php return [];');

        $detector = new ProjectDetector($this->tempDir);

        self::assertTrue($detector->hasRector());
    }

    public function testHasRectorReturnsFalseWhenFileAbsent(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertFalse($detector->hasRector());
    }

    public function testHasPhpUnitReturnsTrueWhenFileExists(): void
    {
        file_put_contents($this->tempDir . '/phpunit.dist.xml', '<phpunit/>');

        $detector = new ProjectDetector($this->tempDir);

        self::assertTrue($detector->hasPhpUnit());
    }

    public function testHasPhpUnitReturnsFalseWhenFileAbsent(): void
    {
        $detector = new ProjectDetector($this->tempDir);

        self::assertFalse($detector->hasPhpUnit());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeComposerJson(array $data): void
    {
        file_put_contents(
            $this->tempDir . '/composer.json',
            json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeComposerLock(array $data): void
    {
        file_put_contents(
            $this->tempDir . '/composer.lock',
            json_encode($data, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES),
        );
    }
}
