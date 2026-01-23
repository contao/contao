<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Loader;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class ContaoFilesystemLoaderWarmer implements CacheWarmerInterface
{
    /**
     * @internal
     */
    public function __construct(
        private readonly ContaoFilesystemLoader $loader,
    ) {
    }

    public function warmUp(string|null $cacheDir = null, string|null $buildDir = null): array
    {
        $this->loader->warmUp();

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
