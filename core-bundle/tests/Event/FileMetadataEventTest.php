<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Event;

use Contao\CoreBundle\Event\FileMetadataEvent;
use Contao\CoreBundle\File\Metadata;
use PHPUnit\Framework\TestCase;

class FileMetadataEventTest extends TestCase
{
    public function testGetsAndSetsMetadata(): void
    {
        $metadata = new Metadata([Metadata::VALUE_TITLE => 'foo']);
        $event = new FileMetadataEvent($metadata);

        $this->assertSame($metadata, $event->getMetadata());

        $newMetadata = new Metadata([Metadata::VALUE_ALT => 'bar']);
        $event->setMetadata($newMetadata);

        $this->assertSame($newMetadata, $event->getMetadata());
    }
}
