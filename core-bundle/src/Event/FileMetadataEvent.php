<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Event;

use Contao\CoreBundle\File\Metadata;

class FileMetadataEvent
{
    private ?Metadata $metadata;

    public function __construct(?Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    public function getMetadata(): ?Metadata
    {
        return $this->metadata;
    }

    public function setMetadata(?Metadata $metadata): void
    {
        $this->metadata = $metadata;
    }
}
