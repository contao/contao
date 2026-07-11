<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Twig\Studio;

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Twig\Environment;

/**
 * @internal
 */
class CacheInvalidator
{
    public function __construct(
        private readonly Environment $twig,
        private readonly ContaoFilesystemLoader $filesystemLoader,
    ) {
    }

    /**
     * Removes cached versions of all templates potentially extending from, using or
     * including a template of the given identifier.
     */
    public function invalidateCache(string $identifier, string|null $themeSlug = null): void
    {
        // Until we have/need a more sophisticated solution, invalidate all Contao
        // templates except for those used in the back end.
        foreach ($this->filesystemLoader->getInheritanceChains($themeSlug) as $currentIdentifier => $chain) {
            if (str_starts_with($currentIdentifier, 'backend/')) {
                continue;
            }

            foreach ($chain as $logicalName) {
                $this->twig->removeCache($logicalName);
            }
        }
    }
}
