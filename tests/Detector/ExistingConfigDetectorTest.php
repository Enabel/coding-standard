<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Detector;

use Enabel\CodingStandard\Detector\ExistingConfigDetector;
use PHPUnit\Framework\TestCase;

final class ExistingConfigDetectorTest extends TestCase
{
    private string $basePath;
    private ExistingConfigDetector $detector;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/coding-standard-detector-test-' . uniqid();
        mkdir($this->basePath);
        $this->detector = new ExistingConfigDetector();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function testDetectReturnsMapOfFileExistence(): void
    {
        file_put_contents($this->basePath . '/existing.txt', '');

        $result = $this->detector->detect($this->basePath, ['existing.txt', 'nonexistent.txt']);

        self::assertSame([
            'existing.txt' => true,
            'nonexistent.txt' => false,
        ], $result);
    }

    public function testDetectRecognizesDirectories(): void
    {
        mkdir($this->basePath . '/subdir');

        $result = $this->detector->detect($this->basePath, ['subdir']);

        self::assertSame(['subdir' => true], $result);
    }

    public function testDetectHandlesTrailingSlashInBasePath(): void
    {
        file_put_contents($this->basePath . '/file.txt', '');

        $result = $this->detector->detect($this->basePath . '/', ['file.txt']);

        self::assertSame(['file.txt' => true], $result);
    }

    public function testGetExistingReturnsOnlyExistingFiles(): void
    {
        file_put_contents($this->basePath . '/file1.txt', '');
        file_put_contents($this->basePath . '/file2.txt', '');

        $result = $this->detector->getExisting($this->basePath, ['file1.txt', 'nonexistent.txt', 'file2.txt']);

        self::assertSame(['file1.txt', 'file2.txt'], $result);
    }

    public function testGetExistingReturnsEmptyArrayWhenNoFilesExist(): void
    {
        $result = $this->detector->getExisting($this->basePath, ['nonexistent1.txt', 'nonexistent2.txt']);

        self::assertSame([], $result);
    }

    public function testHasAnyExistingReturnsTrueWhenAtLeastOneFileExists(): void
    {
        file_put_contents($this->basePath . '/existing.txt', '');

        self::assertTrue($this->detector->hasAnyExisting($this->basePath, ['nonexistent.txt', 'existing.txt']));
    }

    public function testHasAnyExistingReturnsFalseWhenNoFilesExist(): void
    {
        self::assertFalse($this->detector->hasAnyExisting($this->basePath, ['nonexistent1.txt', 'nonexistent2.txt']));
    }

    public function testHasAnyExistingReturnsFalseForEmptyList(): void
    {
        self::assertFalse($this->detector->hasAnyExisting($this->basePath, []));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = array_diff((array) scandir($path), ['.', '..']);
        foreach ($files as $file) {
            $fullPath = $path . '/' . $file;
            if (is_dir($fullPath)) {
                $this->removeDirectory($fullPath);
            } else {
                unlink($fullPath);
            }
        }
        rmdir($path);
    }
}
