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
