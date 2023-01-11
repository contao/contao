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
    public function __construct(private Metadata|null $metadata)
    {
    }

    public function getMetadata(): Metadata|null
    {
        return $this->metadata;
    }

    public function setMetadata(Metadata|null $metadata): void
    {
        $this->metadata = $metadata;
    }
}
