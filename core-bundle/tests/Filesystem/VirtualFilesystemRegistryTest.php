<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Filesystem;

use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

class VirtualFilesystemRegistryTest extends TestCase
{
    public function testLoadsFilesystemsOnlyWhenRequested(): void
    {
        $filesystem = $this->createStub(VirtualFilesystemInterface::class);

        $locator = $this->createMock(ContainerInterface::class);
        $locator
            ->expects($this->once())
            ->method('get')
            ->with('files')
            ->willReturn($filesystem)
        ;

        $filesystems = ['files' => ['prefix' => 'files', 'api_exposure' => true, 'api_path' => '/files']];
        $registry = new VirtualFilesystemRegistry($locator, $filesystems);

        $this->assertTrue($registry->has('files'));
        $this->assertFalse($registry->has('unknown'));
        $this->assertSame($filesystems, $registry->findByTags());
        $this->assertSame($filesystems, $registry->findByTags(['api_exposure' => true]));
        $this->assertSame($filesystems['files'], $registry->getTags('files'));
        $this->assertSame($filesystem, $registry->get('files'));
    }

    public function testHasFilesystemWithoutTags(): void
    {
        $registry = new VirtualFilesystemRegistry(new ServiceLocator([]), ['files' => []]);

        $this->assertTrue($registry->has('files'));
        $this->assertSame([], $registry->getTags('files'));
    }

    #[DataProvider('provideCriteria')]
    public function testFiltersTagsStrictly(array $criteria, array $expected): void
    {
        $filesystems = [
            'files' => ['api_exposure' => true, 'api_path' => '/files', 'optional' => null],
            'backups' => ['api_exposure' => false],
            'other' => ['api_exposure' => 1],
            'untagged' => [],
        ];

        $registry = new VirtualFilesystemRegistry(new ServiceLocator([]), $filesystems);

        $this->assertSame(array_intersect_key($filesystems, array_flip($expected)), $registry->findByTags($criteria));
    }

    public static function provideCriteria(): iterable
    {
        yield 'all' => [[], ['files', 'backups', 'other', 'untagged']];
        yield 'boolean' => [['api_exposure' => true], ['files']];
        yield 'integer' => [['api_exposure' => 1], ['other']];
        yield 'false excludes missing' => [['api_exposure' => false], ['backups']];
        yield 'null excludes missing' => [['optional' => null], ['files']];
        yield 'all criteria match' => [['api_exposure' => true, 'api_path' => '/files'], ['files']];
        yield 'one criterion differs' => [['api_exposure' => true, 'api_path' => '/backups'], []];
        yield 'unknown tag' => [['unknown' => true], []];
    }

    #[DataProvider('provideLookupMethods')]
    public function testRejectsUnknownIdentifiers(string $method): void
    {
        $registry = new VirtualFilesystemRegistry(new ServiceLocator([]), []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A virtual filesystem with the name "unknown" does not exist.');

        $registry->$method('unknown');
    }

    public static function provideLookupMethods(): iterable
    {
        yield ['get'];
        yield ['getTags'];
    }
}
