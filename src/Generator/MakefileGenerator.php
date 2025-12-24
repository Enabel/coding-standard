<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

final class MakefileGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        return [
            'Makefile' => $this->render('Makefile.tpl', [
                'isSymfony' => $config->isSymfonyProject,
                'includePhpCsFixer' => $config->includePhpCsFixer,
                'includePhpStan' => $config->includePhpStan,
                'includeRector' => $config->includeRector,
            ]),
        ];
    }

    public function supports(Configuration $config): bool
    {
        return $config->includeMakefile;
    }

    public function getTargetFiles(): array
    {
        return ['Makefile'];
    }
}
