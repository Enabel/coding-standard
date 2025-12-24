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
