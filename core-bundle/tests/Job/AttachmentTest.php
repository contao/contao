<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Job;

use Contao\CoreBundle\Filesystem\Dbafs\DbafsManager;
use Contao\CoreBundle\Filesystem\MountManager;
use Contao\CoreBundle\Filesystem\VirtualFilesystem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Job\Attachment;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\TranslatableMessage;

class AttachmentTest extends TestCase
{
    private VirtualFilesystemInterface $vfs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->vfs = new VirtualFilesystem(
            (new MountManager())->mount(new InMemoryFilesystemAdapter()),
            $this->createStub(DbafsManager::class),
        );
    }

    public function testGettersReturnProvidedData(): void
    {
        $this->vfs->write('debug.csv', 'foobar');
        $filesystemItem = $this->vfs->get('debug.csv');

        $label = new TranslatableMessage('file_label.debug.csv', [], 'contao_jobs');
        $downloadUrl = 'https://foobar.com/uuid/file';

        $attachment = new Attachment($filesystemItem, $label, $downloadUrl);

        $this->assertSame($filesystemItem, $attachment->getFilesystemItem());
        $this->assertSame('debug.csv', $attachment->getFileName());
        $this->assertSame($label, $attachment->getFileLabel());
        $this->assertSame($downloadUrl, $attachment->getDownloadUrl());
    }

    public function testToStreamedResponseStreamsContentAndSetsHeaders(): void
    {
        $this->vfs->write('debug.csv', 'foobar');
        $filesystemItem = $this->vfs->get('debug.csv');

        $attachment = new Attachment(
            $filesystemItem,
            new TranslatableMessage('file_label.debug.csv', [], 'contao_jobs'),
            'https://foobar.com/uuid/file',
        );

        $response = $attachment->toStreamedResponse();

        $this->assertSame('text/csv', $response->headers->get('Content-Type'));
        $disposition = $response->headers->get('Content-Disposition');
        $this->assertNotNull($disposition);
        $this->assertStringContainsString('attachment', $disposition);
        $this->assertStringContainsString('debug.csv', $disposition);
    }
}
