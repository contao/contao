<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Search\Backend\Provider;

use Contao\CoreBundle\Filesystem\Dbafs\DbafsInterface;
use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Search\Backend\Document;
use Contao\CoreBundle\Search\Backend\Hit;
use Contao\CoreBundle\Search\Backend\IndexUpdateConfig\UpdateAllProvidersConfig;
use Contao\CoreBundle\Search\Backend\Provider\FilesStorageProvider;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use League\Flysystem\Config;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class FilesStorageProviderTest extends AbstractProviderTestCase
{
    public function testSupports(): void
    {
        $provider = new FilesStorageProvider(
            $this->createMock(VirtualFilesystem::class),
            $this->createMock(Security::class),
            $this->createMock(Studio::class),
        );

        $this->assertTrue($provider->supportsType(FilesStorageProvider::TYPE));
        $this->assertFalse($provider->supportsType('foobar'));
    }

    public function testUpdateIndex(): void
    {
        $adapter = new InMemoryFilesystemAdapter();
        $adapter->createDirectory('foo', new Config());
        $adapter->write('foo/bar.jpg', '…', new Config());

        $filesystem = new VirtualFilesystem(
            (new MountManager())->mount($adapter),
            $this->createMock(DbafsManager::class),
        );

        $provider = new FilesStorageProvider(
            $filesystem,
            $this->createMock(Security::class),
            $this->createMock(Studio::class),
        );

        $documents = iterator_to_array($provider->updateIndex(new UpdateAllProvidersConfig()));

        $this->assertCount(1, $documents);

        /** @var Document $document */
        $document = $documents[0];

        $this->assertSame('foo/bar.jpg', $document->getId());
        $this->assertSame(FilesStorageProvider::TYPE, $document->getType());
        $this->assertSame('bar.jpg', $document->getSearchableContent());
        $this->assertSame(['extension:jpg'], $document->getTags());
        $this->assertSame(['path' => 'foo/bar.jpg'], $document->getMetadata());
    }

    public function testUpdateIndexSince(): void
    {
        $dbafs = $this->createMock(DbafsInterface::class);
        $dbafs
            ->method('getRecords')
            ->with('', true)
            ->willReturn(new \ArrayIterator([
                new FilesystemItem(true, 'foo', 3600),
                new FilesystemItem(true, 'bar', 3601),
                new FilesystemItem(true, 'baz', 0),
            ]))
        ;

        $dbafsManager = new DbafsManager();
        $dbafsManager->register($dbafs, '');

        $filesystem = new VirtualFilesystem(
            $this->createMock(MountManager::class),
            $dbafsManager,
        );

        $provider = new FilesStorageProvider(
            $filesystem,
            $this->createMock(Security::class),
            $this->createMock(Studio::class),
        );

        $since = new \DateTimeImmutable('1970-01-01 01:00:00');
        $documents = iterator_to_array($provider->updateIndex(new UpdateAllProvidersConfig($since)));

        $this->assertCount(1, $documents);

        /** @var Document $document */
        $document = $documents[0];

        $this->assertSame('bar', $document->getId());
    }

    public function testIsHitGranted(): void
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->willReturnMap([
                [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'foo', true],
                [ContaoCorePermissions::USER_CAN_ACCESS_PATH, 'bar', false],
            ])
        ;

        $provider = new FilesStorageProvider(
            $this->createMock(VirtualFilesystem::class),
            $security,
            $this->createMock(Studio::class),
        );

        $allowedHit = new Hit(
            (new Document('', '', ''))->withMetadata(
                ['path' => 'foo'],
            ),
            '',
            '',
        );

        $disallowedHit = new Hit(
            (new Document('', '', ''))->withMetadata(
                ['path' => 'bar'],
            ),
            '',
            '',
        );

        $token = $this->createMock(TokenInterface::class);

        $this->assertTrue($provider->isHitGranted($token, $allowedHit));
        $this->assertFalse($provider->isHitGranted($token, $disallowedHit));
    }
}
