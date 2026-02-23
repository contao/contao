<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Filesystem\PublicUri;

use League\Flysystem\Config;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UrlGeneration\PublicUrlGenerator;
use Nyholm\Psr7\Uri;
use Psr\Http\Message\UriInterface;

class FlysystemDefaultProvider implements PublicUriProviderInterface
{
    /**
     * Generate default public URLs for adapters that are a PublicUrlGenerator.
     * See https://flysystem.thephpleague.com/docs/usage/public-urls/.
     */
    public function getUri(FilesystemAdapter $adapter, string $adapterPath, OptionsInterface|null $options): UriInterface|null
    {
        if ($options || !$adapter instanceof PublicUrlGenerator) {
            return null;
        }

        return new Uri($adapter->publicUrl($adapterPath, new Config()));
    }
}
