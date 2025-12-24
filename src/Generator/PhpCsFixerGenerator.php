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

final class PhpCsFixerGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        $files = [];

        $files['.php-cs-fixer.dist.php'] = $this->render('php-cs-fixer.dist.php.tpl', [
            'projectName' => $config->projectName,
            'srcPath' => $config->srcPath,
            'testsPath' => $config->testsPath,
            'isSymfony' => $config->isSymfonyProject,
        ]);

        $files['tools/php-cs-fixer/composer.json'] = $this->render('tools/php-cs-fixer/composer.json.tpl', []);

        return $files;
    }

    public function supports(Configuration $config): bool
    {
        return $config->includePhpCsFixer;
    }

    public function getTargetFiles(): array
    {
        return [
            '.php-cs-fixer.dist.php',
            'tools/php-cs-fixer/composer.json',
        ];
    }
}
