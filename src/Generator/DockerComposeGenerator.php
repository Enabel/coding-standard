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

final class DockerComposeGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        $variables = [
            'projectName' => $config->projectName,
            'phpVersion' => $config->phpVersion,
            'devEnvironment' => $config->devEnvironment,
            'databaseType' => $config->databaseType,
            'databaseVersion' => $config->databaseVersion,
            'databaseImage' => $config->getDatabaseImage(),
            'databasePort' => $config->getDatabasePort(),
            'databaseEnvVars' => $config->getDatabaseEnvVars(),
            'databaseUrl' => $config->getDatabaseUrl(),
            'databaseUrlDocker' => $config->getDatabaseUrl('database'),
            'phpDatabaseExtension' => $config->getPhpDatabaseExtension(),
            'isSymfony' => $config->isSymfonyProject,
        ];

        $files = [
            'compose.yaml' => $this->render('docker/compose.yaml.tpl', $variables),
            'compose.override.yaml' => $this->render('docker/compose.override.yaml.tpl', $variables),
        ];

        if ('docker' === $config->devEnvironment) {
            $files['Dockerfile'] = $this->render('docker/Dockerfile.tpl', $variables);
        }

        if ('symfony-cli' === $config->devEnvironment) {
            $files['.symfony.local.yaml'] = $this->render('docker/symfony.local.yaml.tpl', $variables);
        }

        if ($config->isSymfonyProject) {
            $files['.env.local'] = $this->render('docker/env.local.tpl', $variables);
        }

        return $files;
    }

    public function supports(Configuration $config): bool
    {
        return $config->hasDatabase()
            && in_array($config->devEnvironment, ['symfony-cli', 'docker'], true);
    }

    public function getTargetFiles(): array
    {
        return ['compose.yaml', 'compose.override.yaml', 'Dockerfile', '.symfony.local.yaml', '.env.local'];
    }
}
