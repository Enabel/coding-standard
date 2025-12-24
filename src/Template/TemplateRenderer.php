<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Template;

use RuntimeException;

final class TemplateRenderer
{
    public function __construct(
        private readonly string $templatesPath,
    ) {
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(string $template, array $variables = []): string
    {
        $templateFile = $this->templatesPath . '/' . $template;

        if (!file_exists($templateFile)) {
            throw new RuntimeException(sprintf('Template not found: %s', $template));
        }

        extract($variables, EXTR_SKIP);

        ob_start();

        try {
            include $templateFile;
        } catch (\Throwable $e) {
            ob_end_clean();

            throw new RuntimeException(sprintf(
                'Error rendering template "%s": %s',
                $template,
                $e->getMessage(),
            ), 0, $e);
        }

        return (string) ob_get_clean();
    }

    public function exists(string $template): bool
    {
        return file_exists($this->templatesPath . '/' . $template);
    }
}
