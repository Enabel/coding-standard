<?php

declare(strict_types=1);

/*
 * This file is part of the Enabel Coding Standard.
 * Copyright (c) Enabel <https://github.com/Enabel>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Enabel\CodingStandard\Tests\Config;

use Enabel\CodingStandard\Config\ConflictResolution;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ValueError;

final class ConflictResolutionTest extends TestCase
{
    #[DataProvider('validValuesProvider')]
    public function testFromCreatesEnumFromValidValue(string $value, ConflictResolution $expected): void
    {
        self::assertSame($expected, ConflictResolution::from($value));
    }

    /**
     * @return iterable<string, array{string, ConflictResolution}>
     */
    public static function validValuesProvider(): iterable
    {
        yield 'skip' => ['skip', ConflictResolution::SKIP];
        yield 'replace' => ['replace', ConflictResolution::REPLACE];
        yield 'ask' => ['ask', ConflictResolution::ASK];
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(ValueError::class);

        ConflictResolution::from('invalid');
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        self::assertNull(ConflictResolution::tryFrom('invalid'));
    }

    public function testEnumCasesAreCorrect(): void
    {
        $cases = ConflictResolution::cases();

        self::assertCount(3, $cases);
        self::assertContains(ConflictResolution::SKIP, $cases);
        self::assertContains(ConflictResolution::REPLACE, $cases);
        self::assertContains(ConflictResolution::ASK, $cases);
    }

    public function testEnumValuesAreCorrect(): void
    {
        self::assertSame('skip', ConflictResolution::SKIP->value);
        self::assertSame('replace', ConflictResolution::REPLACE->value);
        self::assertSame('ask', ConflictResolution::ASK->value);
    }
}
