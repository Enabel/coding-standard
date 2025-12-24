<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Template;

use Enabel\CodingStandard\Template\TemplateRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class TemplateRendererTest extends TestCase
{
    private string $templatesPath;

    protected function setUp(): void
    {
        $this->templatesPath = sys_get_temp_dir() . '/coding-standard-test-' . uniqid();
        mkdir($this->templatesPath);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->templatesPath);
    }

    public function testRenderSimpleTemplate(): void
    {
        file_put_contents($this->templatesPath . '/simple.php', 'Hello World');

        $renderer = new TemplateRenderer($this->templatesPath);

        self::assertSame('Hello World', $renderer->render('simple.php'));
    }

    public function testRenderTemplateWithVariables(): void
    {
        file_put_contents($this->templatesPath . '/greeting.php', 'Hello <?= $name ?>!');

        $renderer = new TemplateRenderer($this->templatesPath);

        self::assertSame('Hello John!', $renderer->render('greeting.php', ['name' => 'John']));
    }

    public function testRenderThrowsExceptionForNonExistentTemplate(): void
    {
        $renderer = new TemplateRenderer($this->templatesPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found: nonexistent.php');

        $renderer->render('nonexistent.php');
    }

    public function testRenderThrowsExceptionOnTemplateError(): void
    {
        file_put_contents($this->templatesPath . '/error.php', '<?php throw new \Exception("Template error");');

        $renderer = new TemplateRenderer($this->templatesPath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error rendering template "error.php": Template error');

        $renderer->render('error.php');
    }

    public function testExistsReturnsTrueForExistingTemplate(): void
    {
        file_put_contents($this->templatesPath . '/exists.php', '');

        $renderer = new TemplateRenderer($this->templatesPath);

        self::assertTrue($renderer->exists('exists.php'));
    }

    public function testExistsReturnsFalseForNonExistentTemplate(): void
    {
        $renderer = new TemplateRenderer($this->templatesPath);

        self::assertFalse($renderer->exists('nonexistent.php'));
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
