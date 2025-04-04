<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\IDE;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class NamespaceLookupFileWarmer implements CacheWarmerInterface
{
    public const CONTAO_IDE_DIR = 'contao-ide';

    public function __construct(
        private readonly NamespaceLookupFileGenerator $namespaceLookupFileGenerator,
        private readonly string $environment,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, string|null $buildDir = null): array
    {
        if ('dev' !== $this->environment || null === $buildDir) {
            return [];
        }

        try {
            $this->namespaceLookupFileGenerator->write(Path::join($buildDir, self::CONTAO_IDE_DIR));
        } catch (IOException) {
            // ignore
        }

        return [];
    }
}
