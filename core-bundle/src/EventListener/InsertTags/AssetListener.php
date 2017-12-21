<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\EventListener\InsertTags;

use Symfony\Component\Asset\Packages;

class AssetListener
{
    /**
     * @var Packages
     */
    private $packages;

    /**
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

    /**
     * Replaces the "asset" insert tag.
     *
     * @param string $tag
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
