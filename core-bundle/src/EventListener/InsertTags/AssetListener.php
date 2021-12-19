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
    private Packages $packages;

    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    /**
     * Replaces the "asset" insert tag.
     *
     * @return string|false
     */
    public function onReplaceInsertTags(string $tag)
    {
        $chunks = explode('::', $tag);

        if ('asset' !== $chunks[0]) {
            return false;
        }

        $url = $this->packages->getUrl($chunks[1], $chunks[2] ?? null);

        // Contao paths are relative to the <base> tag, so remove leading slashes
        return ltrim($url, '/');
    }
}
