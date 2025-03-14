<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\IDE;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class NamespaceLookupFileWarmer implements CacheWarmerInterface
{
    public const TARGET_DIR = 'var/contao-twig';

    public function __construct(
        private readonly NamespaceLookupFileGenerator $namespaceLookupFileGenerator,
        private readonly string $environment,
        private readonly string $projectDir,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, string|null $buildDir = null): array
    {
        if ('dev' !== $this->environment) {
            return [];
        }

        try {
            $this->namespaceLookupFileGenerator->write(Path::join($this->projectDir, self::TARGET_DIR));
        } catch (IOException) {
            // ignore
        }

        return [];
    }
}
