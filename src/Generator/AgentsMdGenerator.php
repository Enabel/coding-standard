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

final class AgentsMdGenerator extends AbstractGenerator
{
    private const string MARKER_BEGIN = '<!-- BEGIN ENABEL CODING STANDARD -->';
    private const string MARKER_END = '<!-- END ENABEL CODING STANDARD -->';

    public function generate(Configuration $config): array
    {
        $variables = [
            'isSymfony' => $config->isSymfonyProject,
            'srcPath' => $config->srcPath,
            'testsPath' => $config->testsPath,
        ];

        $existingPath = $config->outputDir . '/AGENTS.md';
        if (file_exists($existingPath)) {
            $content = $this->mergeWithExisting($existingPath, $variables);
        } else {
            $content = $this->render('agents-md.tpl', $variables);
        }

        return ['AGENTS.md' => $content];
    }

    public function supports(Configuration $config): bool
    {
        return true;
    }

    public function getTargetFiles(): array
    {
        return ['AGENTS.md'];
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function mergeWithExisting(string $path, array $variables): string
    {
        $existing = (string) file_get_contents($path);
        $section = $this->render('agents-md-section.tpl', $variables);

        // Replace existing section if present
        $pattern = '/' . preg_quote(self::MARKER_BEGIN, '/') . '.*?' . preg_quote(self::MARKER_END, '/') . '/s';
        if (preg_match($pattern, $existing)) {
            return preg_replace($pattern, trim($section), $existing) ?? $existing;
        }

        // Append section
        return rtrim($existing) . "\n\n" . $section;
    }
}
