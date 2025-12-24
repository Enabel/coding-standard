<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

final class DdevGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        return [
            '.ddev/config.yaml' => $this->render('ddev/config.yaml.tpl', [
                'projectName' => $config->projectName,
                'phpVersion' => $config->phpVersion,
                'isSymfony' => $config->isSymfonyProject,
            ]),
        ];
    }

    public function supports(Configuration $config): bool
    {
        return $config->includeDdev;
    }

    public function getTargetFiles(): array
    {
        return ['.ddev/config.yaml'];
    }
}
