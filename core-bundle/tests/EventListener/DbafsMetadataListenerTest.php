<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Event\DbafsMetadataEvent;
use Contao\CoreBundle\EventListener\DbafsMetadataListener;
use Contao\CoreBundle\File\Metadata;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image\ImportantPart;

class DbafsMetadataListenerTest extends TestCase
{
    public function testAddsMetadata(): void
    {
        $event = new DbafsMetadataEvent('tl_files', $this->getDemoRowData());

        $this->assertEmpty($event->getExtraMetadata());

        (new DbafsMetadataListener())($event);

        $extraMetadata = $event->getExtraMetadata();

        $importantPart = $extraMetadata['importantPart'] ?? null;
        $this->assertInstanceOf(ImportantPart::class, $importantPart);
        $this->assertSame(0.1, $importantPart->getX());
        $this->assertSame(0.2, $importantPart->getY());
        $this->assertSame(0.3, $importantPart->getWidth());
        $this->assertSame(0.4, $importantPart->getHeight());

        $metadata = $extraMetadata['metadata']['de'] ?? null;
        $this->assertInstanceOf(Metadata::class, $metadata);
        $this->assertSame('my title', $metadata->getTitle());
        $this->assertSame('9e41', $metadata->getUuid());
    }

    public function testOnlyRunsOnDefaultTable(): void
    {
        $event = new DbafsMetadataEvent('tl_foo', $this->getDemoRowData());

        (new DbafsMetadataListener())($event);

        $this->assertEmpty($event->getExtraMetadata());
    }

    private function getDemoRowData(): array
    {
        return [
            'uuid' => '9e41',
            'path' => 'foo/bar',
            'importantPartX' => 0.1,
            'importantPartY' => 0.2,
            'importantPartWidth' => 0.3,
            'importantPartHeight' => 0.4,
            'meta' => serialize([
                'de' => [Metadata::VALUE_TITLE => 'my title'],
            ]),
        ];
    }
}
