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
use Enabel\CodingStandard\Generator\PhpCsFixerGenerator;
use Enabel\CodingStandard\Template\TemplateRenderer;
use PHPUnit\Framework\TestCase;

final class PhpCsFixerGeneratorTest extends TestCase
{
    private PhpCsFixerGenerator $generator;

    protected function setUp(): void
    {
        $templatesPath = dirname(__DIR__, 2) . '/templates';
        $renderer = new TemplateRenderer($templatesPath);
        $this->generator = new PhpCsFixerGenerator($renderer);
    }

    public function testSupportsReturnsTrueWhenPhpCsFixerEnabled(): void
    {
        $config = $this->createConfiguration(includePhpCsFixer: true);

        self::assertTrue($this->generator->supports($config));
    }

    public function testSupportsReturnsFalseWhenPhpCsFixerDisabled(): void
    {
        $config = $this->createConfiguration(includePhpCsFixer: false);

        self::assertFalse($this->generator->supports($config));
    }

    public function testGetTargetFilesReturnsExpectedFiles(): void
    {
        $expected = [
            '.php-cs-fixer.dist.php',
            'tools/php-cs-fixer/composer.json',
        ];

        self::assertSame($expected, $this->generator->getTargetFiles());
    }

    public function testGenerateReturnsExpectedFileKeys(): void
    {
        $config = $this->createConfiguration(includePhpCsFixer: true);

        $files = $this->generator->generate($config);

        self::assertArrayHasKey('.php-cs-fixer.dist.php', $files);
        self::assertArrayHasKey('tools/php-cs-fixer/composer.json', $files);
    }

    public function testGenerateCreatesValidPhpCsFixerConfig(): void
    {
        $config = $this->createConfiguration(
            includePhpCsFixer: true,
            srcPath: 'src',
            testsPath: 'tests',
        );

        $files = $this->generator->generate($config);
        $content = $files['.php-cs-fixer.dist.php'];

        self::assertStringContainsString('PhpCsFixer\Config', $content);
        self::assertStringContainsString('/src', $content);
        self::assertStringContainsString('/tests', $content);
    }

    public function testGenerateIncludesSymfonyRulesWhenSymfonyProject(): void
    {
        $config = $this->createConfiguration(
            includePhpCsFixer: true,
            isSymfonyProject: true,
        );

        $files = $this->generator->generate($config);
        $content = $files['.php-cs-fixer.dist.php'];

        self::assertStringContainsString('@Symfony', $content);
    }

    public function testGenerateCreatesValidComposerJson(): void
    {
        $config = $this->createConfiguration(includePhpCsFixer: true);

        $files = $this->generator->generate($config);
        $content = $files['tools/php-cs-fixer/composer.json'];

        $decoded = json_decode($content, true);

        self::assertIsArray($decoded);
        /** @var array<string, mixed> $decoded */
        self::assertArrayHasKey('require', $decoded);
        self::assertIsArray($decoded['require']);
        self::assertArrayHasKey('friendsofphp/php-cs-fixer', $decoded['require']);
    }

    private function createConfiguration(
        bool $includePhpCsFixer = false,
        bool $isSymfonyProject = false,
        string $srcPath = 'src',
        string $testsPath = 'tests',
    ): Configuration {
        return new Configuration(
            projectName: 'test-project',
            phpVersion: '8.4',
            phpstanLevel: 9,
            isSymfonyProject: $isSymfonyProject,
            symfonyVersion: null,
            ciProvider: 'none',
            includeDdev: false,
            includeMakefile: false,
            includePhpCsFixer: $includePhpCsFixer,
            includePhpStan: false,
            includeRector: false,
            includePhpUnit: false,
            srcPath: $srcPath,
            testsPath: $testsPath,
            outputDir: '.',
            conflictResolution: ConflictResolution::ASK,
        );
    }
}
