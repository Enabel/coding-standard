<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Template\TemplateRenderer;

abstract class AbstractGenerator implements GeneratorInterface
{
    public function __construct(
        protected readonly TemplateRenderer $renderer,
    ) {
    }

    /**
     * @param array<string, mixed> $variables
     */
    protected function render(string $template, array $variables = []): string
    {
        return $this->renderer->render($template, $variables);
    }
}
