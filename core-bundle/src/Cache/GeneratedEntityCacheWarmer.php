<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Cache;

use Contao\CoreBundle\Orm\EntityExtensionCollector;
use Contao\CoreBundle\Orm\EntityFactory;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class GeneratedEntityCacheWarmer implements CacheWarmerInterface
{
    public function __construct(private EntityExtensionCollector $entityExtensionCollector, private EntityFactory $factory, private string $entityDirectory)
    {
    }

    public function warmUp(string $cacheDir): array
    {
        $this->ensureCacheDirectoryExists($cacheDir);

        $extensions = $this->entityExtensionCollector->collect();

        $this->factory->generateEntityClasses($this->entityDirectory, $extensions);

        return [];
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function ensureCacheDirectoryExists(string $cacheDir): void
    {
        $filesystem = new Filesystem();

        if (!is_dir($cacheDir)) {
            if (false === $filesystem->mkdir($cacheDir)) {
                throw new \RuntimeException(sprintf('Unable to create the Contao Entity directory "%s".', $cacheDir));
            }
        } elseif (!is_writable($cacheDir)) {
            throw new \RuntimeException(sprintf('The Contao Entity directory "%s" is not writeable for the current system user.', $cacheDir));
        }
    }
}
