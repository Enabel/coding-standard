<?php

declare(strict_types=1);

namespace Enabel\CodingStandard\Detector;

final class ExistingConfigDetector
{
    /**
     * @param list<string> $filesToCheck
     *
     * @return array<string, bool> Map of file paths to existence status
     */
    public function detect(string $basePath, array $filesToCheck): array
    {
        $result = [];

        foreach ($filesToCheck as $file) {
            $fullPath = rtrim($basePath, '/') . '/' . $file;
            $result[$file] = file_exists($fullPath) || is_dir($fullPath);
        }

        return $result;
    }

    /**
     * @param list<string> $filesToCheck
     *
     * @return list<string> List of existing files
     */
    public function getExisting(string $basePath, array $filesToCheck): array
    {
        $existing = [];

        foreach ($this->detect($basePath, $filesToCheck) as $file => $exists) {
            if ($exists) {
                $existing[] = $file;
            }
        }

        return $existing;
    }

    /**
     * @param list<string> $filesToCheck
     */
    public function hasAnyExisting(string $basePath, array $filesToCheck): bool
    {
        return [] !== $this->getExisting($basePath, $filesToCheck);
    }
}
