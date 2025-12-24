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

final class ToolsGitIgnoreGenerator extends AbstractGenerator
{
    public function generate(Configuration $config): array
    {
        return [
            'tools/.gitignore' => $this->render('tools/.gitignore.tpl'),
        ];
    }

    public function supports(Configuration $config): bool
    {
        return $config->hasAnyTool();
    }

    public function getTargetFiles(): array
    {
        return [
            'tools/.gitignore',
        ];
    }
}
