<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\EventListener\InsertTags;

use Symfony\Component\Asset\Packages;

/**
 * @internal
 */
class AssetListener
{
    public function __construct(private Packages $packages)
    {
    }

    /**
     * Replaces the "asset" insert tag.
     */
    public function onReplaceInsertTags(string $tag): string|false
    {
        $chunks = explode('::', $tag);

        if ('asset' !== $chunks[0]) {
            return false;
        }

        return $this->packages->getUrl($chunks[1], $chunks[2] ?? null);
    }
}
