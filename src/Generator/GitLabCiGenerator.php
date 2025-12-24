<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

final class GitLabCiGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        return [
            '.gitlab-ci.yml' => $this->render('ci/gitlab-ci.yml.tpl', [
                'phpVersion' => $config->phpVersion,
                'isSymfony' => $config->isSymfonyProject,
                'includePhpCsFixer' => $config->includePhpCsFixer,
                'includePhpStan' => $config->includePhpStan,
                'includeRector' => $config->includeRector,
                'hasDatabase' => $config->hasDatabase(),
                'databaseType' => $config->databaseType,
                'databaseImage' => $config->getDatabaseImage(),
                'databasePort' => $config->getDatabasePort(),
                'databaseUrl' => $config->getDatabaseUrl(),
                'databaseEnvVars' => $config->getDatabaseEnvVars(),
                'phpDatabaseExtension' => $config->getPhpDatabaseExtension(),
            ]),
        ];
    }

    public function supports(Configuration $config): bool
    {
        return 'gitlab' === $config->ciProvider;
    }

    public function getTargetFiles(): array
    {
        return ['.gitlab-ci.yml'];
    }
}
