<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

final class RectorGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        $files = [];

        $files['rector.php'] = $this->render('rector.php.tpl', [
            'srcPath' => $config->srcPath,
            'testsPath' => $config->testsPath,
            'phpVersionNumber' => $config->getPhpVersionNumber(),
            'isSymfony' => $config->isSymfonyProject,
        ]);

        $files['tools/rector/composer.json'] = $this->render('tools/rector/composer.json.tpl', [
            'isSymfony' => $config->isSymfonyProject,
        ]);

        return $files;
    }

    public function supports(Configuration $config): bool
    {
        return $config->includeRector;
    }

    public function getTargetFiles(): array
    {
        return [
            'rector.php',
            'tools/rector/composer.json',
        ];
    }
}
