<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

final class PhpUnitGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        return [
            'phpunit.dist.xml' => $this->render('phpunit.dist.xml.tpl', [
                'srcPath' => $config->srcPath,
                'testsPath' => $config->testsPath,
                'isSymfony' => $config->isSymfonyProject,
            ]),
        ];
    }

    public function supports(Configuration $config): bool
    {
        return $config->includePhpUnit;
    }

    public function getTargetFiles(): array
    {
        return ['phpunit.dist.xml'];
    }
}
