<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\Dbafs;

class RetrieveDbafsMetadataEvent extends AbstractDbafsMetadataEvent
{
    public function set(string $metadataKey, mixed $metadataValue): void
    {
        $this->extraMetadata->set($metadataKey, $metadataValue);
    }
}
