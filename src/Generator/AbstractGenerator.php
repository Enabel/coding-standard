<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Template\TemplateRenderer;

abstract class AbstractGenerator implements GeneratorInterface
{
    public function __construct(
        protected readonly TemplateRenderer $renderer,
    ) {}

    /**
     * @param array<string, mixed> $variables
     */
    protected function render(string $template, array $variables = []): string
    {
        return $this->renderer->render($template, $variables);
    }
}
