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

use League\Flysystem\FilesystemAdapter;
use Psr\Http\Message\UriInterface;

interface PublicUriProviderInterface
{
    /**
     * Returns a public URI for this resource or null if this provider does not
     * support the given adapter/path/options.
     */
    public function getUri(FilesystemAdapter $adapter, string $adapterPath, OptionsInterface|null $options): UriInterface|null;
}
