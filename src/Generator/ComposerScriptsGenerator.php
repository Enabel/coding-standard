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

/**
 * This generator creates a composer-scripts.json file that shows what scripts
 * should be added to the project's composer.json.
 */
final class ComposerScriptsGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
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

        // Build QA script
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

        $content = "# Add these scripts to your composer.json \"scripts\" section:\n\n";
        $content .= json_encode(['scripts' => $scripts], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $content .= "\n";

        return [
            'composer-scripts.json' => $content,
        ];
    }

    public function supports(Configuration $config): bool
    {
        return $config->hasAnyTool();
    }

    public function getTargetFiles(): array
    {
        return ['composer-scripts.json'];
    }
}
