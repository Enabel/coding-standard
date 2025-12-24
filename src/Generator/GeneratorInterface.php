<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Generator;

use Enabel\CodingStandard\Config\Configuration;

interface GeneratorInterface
{
    /**
     * Generate files based on configuration.
     *
     * @return array<string, string> Map of relative file paths to content
     */
    public function generate(Configuration $config): array;

    /**
     * Check if this generator should run for the given configuration.
     */
    public function supports(Configuration $config): bool;

    /**
     * Get list of files this generator may create.
     *
     * @return list<string>
     */
    public function getTargetFiles(): array;
}
