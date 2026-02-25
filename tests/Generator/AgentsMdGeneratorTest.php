<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Generator;

use Enabel\CodingStandard\Config\Configuration;
use Enabel\CodingStandard\Config\ConflictResolution;
use Enabel\CodingStandard\Generator\AgentsMdGenerator;
use Enabel\CodingStandard\Template\TemplateRenderer;
use PHPUnit\Framework\TestCase;

final class AgentsMdGeneratorTest extends TestCase
{
    private AgentsMdGenerator $generator;
    private string $tempDir;

    protected function setUp(): void
    {
        $templatesPath = dirname(__DIR__, 2) . '/templates';
        $renderer = new TemplateRenderer($templatesPath);
        $this->generator = new AgentsMdGenerator($renderer);
        $this->tempDir = sys_get_temp_dir() . '/agents-md-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $agentsFile = $this->tempDir . '/AGENTS.md';
        if (file_exists($agentsFile)) {
            unlink($agentsFile);
        }
        rmdir($this->tempDir);
    }

    public function testSupportsAlwaysReturnsTrue(): void
    {
        $config = $this->createConfiguration();

        self::assertTrue($this->generator->supports($config));
    }

    public function testGenerateCreatesFullFileWhenNoExisting(): void
    {
        $config = $this->createConfiguration();

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('AGENTS.md', $files);
        self::assertStringContainsString('# AGENTS.md', $files['AGENTS.md']);
    }

    public function testGenerateIncludesSymfonySectionsForSymfonyProject(): void
    {
        $config = $this->createConfiguration(isSymfonyProject: true);

        $files = $this->generator->generate($config);
        $content = $files['AGENTS.md'];

        self::assertStringContainsString('Controllers MUST be invokable', $content);
        self::assertStringContainsString('ULID', $content);
        self::assertStringContainsString('DateTimeImmutable', $content);
        self::assertStringContainsString('Messenger', $content);
        self::assertStringContainsString('Flysystem', $content);
        self::assertStringContainsString('LiipImagineBundle', $content);
        self::assertStringContainsString('Stimulus', $content);
        self::assertStringContainsString('Twig Components', $content);
        self::assertStringContainsString('Live Components', $content);
    }

    public function testGenerateExcludesSymfonySectionsForNonSymfonyProject(): void
    {
        $config = $this->createConfiguration(isSymfonyProject: false);

        $files = $this->generator->generate($config);
        $content = $files['AGENTS.md'];

        self::assertStringNotContainsString('Controllers MUST be invokable', $content);
        self::assertStringNotContainsString('Stimulus', $content);
        self::assertStringContainsString('Conventional commits', $content);
        self::assertStringContainsString('TDD', $content);
    }

    public function testGenerateUsesConfiguredPaths(): void
    {
        $config = $this->createConfiguration(srcPath: 'lib', testsPath: 'spec');

        $files = $this->generator->generate($config);
        $content = $files['AGENTS.md'];

        self::assertStringContainsString('lib/', $content);
        self::assertStringContainsString('spec/', $content);
    }

    public function testGenerateAppendsToExistingFile(): void
    {
        $existingContent = "# AGENTS.md\n\nExisting instructions for the team.\n";
        file_put_contents($this->tempDir . '/AGENTS.md', $existingContent);

        $config = $this->createConfiguration(outputDir: $this->tempDir);

        $files = $this->generator->generate($config);
        $content = $files['AGENTS.md'];

        self::assertStringContainsString('Existing instructions for the team.', $content);
        self::assertStringContainsString('<!-- BEGIN ENABEL CODING STANDARD -->', $content);
        self::assertStringContainsString('## Project Conventions', $content);
        self::assertStringContainsString('<!-- END ENABEL CODING STANDARD -->', $content);
    }

    public function testGenerateReplacesExistingSectionOnRerun(): void
    {
        $existingContent = "# AGENTS.md\n\nCustom rules.\n\n<!-- BEGIN ENABEL CODING STANDARD -->\nOld content\n<!-- END ENABEL CODING STANDARD -->\n";
        file_put_contents($this->tempDir . '/AGENTS.md', $existingContent);

        $config = $this->createConfiguration(outputDir: $this->tempDir);

        $files = $this->generator->generate($config);
        $content = $files['AGENTS.md'];

        self::assertStringContainsString('Custom rules.', $content);
        self::assertStringNotContainsString('Old content', $content);
        self::assertStringContainsString('## Project Conventions', $content);
        self::assertSame(1, substr_count($content, '<!-- BEGIN ENABEL CODING STANDARD -->'));
    }

    public function testGetTargetFilesReturnsExpectedFiles(): void
    {
        self::assertSame(['AGENTS.md'], $this->generator->getTargetFiles());
    }

    private function createConfiguration(
        bool $isSymfonyProject = false,
        string $srcPath = 'src',
        string $testsPath = 'tests',
        ?string $outputDir = null,
    ): Configuration {
        return new Configuration(
            projectName: 'test-project',
            phpVersion: '8.4',
            phpstanLevel: 9,
            isSymfonyProject: $isSymfonyProject,
            symfonyVersion: $isSymfonyProject ? '8.0' : null,
            ciProvider: 'none',
            devEnvironment: 'local',
            includeMakefile: false,
            includePhpCsFixer: false,
            includePhpStan: false,
            includeRector: false,
            includePhpUnit: false,
            srcPath: $srcPath,
            testsPath: $testsPath,
            outputDir: $outputDir ?? $this->tempDir,
            conflictResolution: ConflictResolution::ASK,
        );
    }
}
