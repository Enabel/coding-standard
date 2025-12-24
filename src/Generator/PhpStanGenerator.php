<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

final class PhpStanGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        $files = [];

        $files['phpstan.neon'] = $this->render('phpstan.neon.tpl', [
            'phpstanLevel' => $config->phpstanLevel,
            'srcPath' => $config->srcPath,
            'testsPath' => $config->testsPath,
        ]);

        $files['tools/phpstan/composer.json'] = $this->render('tools/phpstan/composer.json.tpl', [
            'isSymfony' => $config->isSymfonyProject,
        ]);

        return $files;
    }

    public function supports(Configuration $config): bool
    {
        return $config->includePhpStan;
    }

    public function getTargetFiles(): array
    {
        return [
            'phpstan.neon',
            'tools/phpstan/composer.json',
        ];
    }
}
