<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\Fixtures\HttpKernel;

use Contao\ManagerPlugin\PluginLoader;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class PluginLoaderCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private readonly PluginLoader $pluginLoader)
    {
    }

    public function warmUp(string $cacheDir, string|null $buildDir = null): array
    {
        file_put_contents(Path::join($cacheDir, 'plugin-loader-warmer'), $this->pluginLoader::class);

        return [];
    }

    public function isOptional(): bool
    {
        return true;
    }
}
