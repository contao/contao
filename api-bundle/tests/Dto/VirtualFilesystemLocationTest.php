<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Dto;

use Contao\ApiBundle\Dto\VirtualFilesystemLocation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Uid\Uuid;

final class VirtualFilesystemLocationTest extends TestCase
{
    public function testKeepsTheIdentifierTypeExplicit(): void
    {
        $uuid = '171bb68d-0094-4f6c-88f5-9b83c0d01521';

        $this->assertSame($uuid, VirtualFilesystemLocation::fromArray(['path' => $uuid])->toLocation());
        $location = VirtualFilesystemLocation::fromArray(['uuid' => $uuid])->toLocation();
        $this->assertInstanceOf(Uuid::class, $location);
        $this->assertSame($uuid, $location->toRfc4122());
    }

    #[DataProvider('provideInvalidSelectors')]
    public function testRejectsInvalidOrAmbiguousSelectors(array $selector): void
    {
        $this->expectException(BadRequestHttpException::class);
        VirtualFilesystemLocation::fromArray($selector);
    }

    public static function provideInvalidSelectors(): iterable
    {
        yield 'missing' => [[]];
        yield 'empty path' => [['path' => '']];
        yield 'invalid uuid' => [['uuid' => 'example.txt']];
        yield 'both' => [['path' => 'example.txt', 'uuid' => '171bb68d-0094-4f6c-88f5-9b83c0d01521']];
        yield 'null' => [['path' => null]];
        yield 'array' => [['path' => ['example.txt']]];
        yield 'unknown' => [['path' => 'example.txt', 'type' => 'path']];
    }
}
