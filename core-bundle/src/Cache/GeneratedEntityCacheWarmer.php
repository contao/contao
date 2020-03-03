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
        $this->ensureCacheDirectory($cacheDir);

        $entities = $this->entityCollector->collect();
        $extensions = $this->extensionCollector->collect();

        $this->factory->generateEntityClasses($entities, $extensions);
    }

    public function isOptional(): bool
    {
        return false;
    }

    private function ensureCacheDirectory($cacheDir)
    {
        $directory = sprintf('%s/contao/entities', $cacheDir);

        if (!is_dir($directory)) {
            if (false === @mkdir($directory, 0777, true)) {
                throw new \RuntimeException(sprintf('Unable to create the Contao Entity directory "%s".', $directory));
            }
        } elseif (!is_writable($directory)) {
            throw new \RuntimeException(sprintf('The Contao Entity directory "%s" is not writeable for the current system user.', $directory));
        }
    }
}
