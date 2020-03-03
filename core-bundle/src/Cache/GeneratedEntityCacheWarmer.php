<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Cache;

use Contao\CoreBundle\Orm\Collector\EntityCollector;
use Contao\CoreBundle\Orm\Collector\ExtensionCollector;
use Contao\CoreBundle\Orm\EntityFactory;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class GeneratedEntityCacheWarmer implements CacheWarmerInterface
{
    private $entityCollector;
    private $extensionCollector;

    private $factory;

    public function __construct(EntityCollector $entityCollector, ExtensionCollector $extensionCollector, EntityFactory $factory)
    {
        $this->entityCollector = $entityCollector;
        $this->extensionCollector = $extensionCollector;

        $this->factory = $factory;
    }

    public function warmUp($cacheDir): void
    {
        $directory = sprintf('%s/contao/entities', $cacheDir);

        $this->ensureCacheDirectoryExists($directory);

        $entities = $this->entityCollector->collect();
        $extensions = $this->extensionCollector->collect();

        $this->factory->generateEntityClasses($directory, $entities, $extensions);
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function ensureCacheDirectoryExists($cacheDir)
    {
        if (!is_dir($cacheDir)) {
            if (false === @mkdir($cacheDir, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the Contao Entity directory "%s".', $directory));
            }
        } elseif (!is_writable($cacheDir)) {
            throw new \RuntimeException(sprintf('The Contao Entity directory "%s" is not writeable for the current system user.', $directory));
        }
    }
}
