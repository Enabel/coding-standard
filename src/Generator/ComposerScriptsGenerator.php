<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

final class ComposerScriptsGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        $composerJsonPath = $config->outputDir . '/composer.json';
        if (!is_file($composerJsonPath)) {
            return [];
        }

        $composerJson = json_decode((string) file_get_contents($composerJsonPath), true);
        if (!is_array($composerJson)) {
            return [];
        }

        $existingScripts = \is_array($composerJson['scripts'] ?? null) ? $composerJson['scripts'] : [];
        $composerJson['scripts'] = array_merge($existingScripts, $this->buildScripts($config));

        if ($config->includeFoundry) {
            $existingRequireDev = \is_array($composerJson['require-dev'] ?? null) ? $composerJson['require-dev'] : [];
            $composerJson['require-dev'] = array_merge($existingRequireDev, [
                'dama/doctrine-test-bundle' => '^8.0',
                'zenstruck/foundry' => '^2.0',
            ]);
        }

        $content = json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        return [
            'composer.json' => $content,
        ];
    }

    public function supports(Configuration $config): bool
    {
        return $config->hasAnyTool() || $config->includeFoundry;
    }

    public function getTargetFiles(): array
    {
        return ['composer.json'];
    }

    /**
     * @return array<string, string|list<string>>
     */
    private function buildScripts(Configuration $config): array
    {
        $scripts = [];

        if ($config->includePhpCsFixer) {
            $scripts['csf'] = 'tools/php-cs-fixer/vendor/bin/php-cs-fixer fix --dry-run --diff';
            $scripts['csf-fix'] = 'tools/php-cs-fixer/vendor/bin/php-cs-fixer fix';
        }

        if ($config->includePhpStan) {
            $scripts['stan'] = 'tools/phpstan/vendor/bin/phpstan analyse';
        }

        if ($config->includeRector) {
            $scripts['rector'] = 'tools/rector/vendor/bin/rector process --dry-run';
            $scripts['rector-fix'] = 'tools/rector/vendor/bin/rector process';
        }

        $scripts['test'] = 'bin/phpunit';

        $qaScripts = [];
        if ($config->includePhpCsFixer) {
            $qaScripts[] = '@csf';
        }
        if ($config->includePhpStan) {
            $qaScripts[] = '@stan';
        }
        $qaScripts[] = '@test';
        $scripts['qa'] = $qaScripts;

        if ($config->isSymfonyProject) {
            $scripts['lint-yaml'] = 'bin/console lint:yaml config --parse-tags';
            $scripts['lint-twig'] = 'bin/console lint:twig templates';
            $scripts['lint-container'] = 'bin/console lint:container';
            $scripts['lint-composer'] = '@composer validate --no-check-publish';
            $scripts['lint'] = ['@lint-yaml', '@lint-container', '@lint-twig', '@lint-composer'];
        }

        return $scripts;
    }
}
