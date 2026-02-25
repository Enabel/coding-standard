<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Detector;

final readonly class ProjectDetector
{
    public function __construct(
        private string $projectDir,
    ) {
    }

    public function getProjectName(): ?string
    {
        $composerJson = $this->readComposerJson();
        if (null === $composerJson) {
            return null;
        }

        $name = $composerJson['name'] ?? null;
        if (!\is_string($name)) {
            return null;
        }

        $parts = explode('/', $name);

        return end($parts);
    }

    public function getPhpVersion(): ?string
    {
        $composerJson = $this->readComposerJson();
        if (null === $composerJson) {
            return null;
        }

        $constraint = $composerJson['require']['php'] ?? null;
        if (!\is_string($constraint)) {
            return null;
        }

        return $this->parsePhpConstraint($constraint);
    }

    public function isSymfonyProject(): bool
    {
        $composerJson = $this->readComposerJson();
        if (null === $composerJson) {
            return false;
        }

        return isset($composerJson['require']['symfony/framework-bundle']);
    }

    public function getSymfonyVersion(): ?string
    {
        // Try composer.lock first for exact installed version
        $lock = $this->readComposerLock();
        if (null !== $lock) {
            foreach ($lock['packages'] ?? [] as $package) {
                if ('symfony/framework-bundle' === ($package['name'] ?? null)) {
                    return $this->mapToSupportedSymfonyVersion(ltrim((string) $package['version'], 'v'));
                }
            }
        }

        // Fallback to composer.json constraint
        $composerJson = $this->readComposerJson();
        if (null === $composerJson) {
            return null;
        }

        $constraint = $composerJson['require']['symfony/framework-bundle'] ?? null;
        if (!\is_string($constraint)) {
            return null;
        }

        return $this->parseSymfonyConstraint($constraint);
    }

    private function mapToSupportedSymfonyVersion(string $version): ?string
    {
        if (str_starts_with($version, '8.')) {
            return '8.0';
        }
        if (str_starts_with($version, '7.')) {
            return '7.4';
        }

        return null;
    }

    private function parseSymfonyConstraint(string $constraint): ?string
    {
        if (preg_match('/(\d+)\./', $constraint, $matches)) {
            $major = (int) $matches[1];

            return $major >= 8 ? '8.0' : ($major >= 7 ? '7.4' : null);
        }

        return null;
    }

    private function parsePhpConstraint(string $constraint): ?string
    {
        $supported = ['8.3', '8.4', '8.5'];

        if (preg_match('/(\d+\.\d+)/', $constraint, $matches)) {
            $version = $matches[1];
            if (\in_array($version, $supported, true)) {
                return $version;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readComposerJson(): ?array
    {
        return $this->readJsonFile($this->projectDir . '/composer.json');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readComposerLock(): ?array
    {
        return $this->readJsonFile($this->projectDir . '/composer.lock');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readJsonFile(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if (false === $content) {
            return null;
        }

        $data = json_decode($content, true);

        return \is_array($data) ? $data : null;
    }
}
