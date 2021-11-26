<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\BackendTheme;

use Contao\BackendUser;
use Contao\Config;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Symfony\WebpackEncoreBundle\Exception\UndefinedBuildException;
use Webmozart\PathUtil\Path;

/**
 * Aggregate the different entry points configured in the container.
 *
 * Retrieve the EntrypointLookup instance from the given key.
 *
 * @internal
 */
class EncoreEntrypointLookupCollection implements EntrypointLookupCollectionInterface
{
    private string $webDir;

    public function __construct(string $webDir)
    {
        $this->webDir = $webDir;
    }

    /**
     * Returns the correct entrypoint lookup.
     * Defaults to the entrypoints.json in the Contao core backend theme.
     *
     * @throws UndefinedBuildException if the backend theme does not exist
     */
    public function getEntrypointLookup(string $buildName = null): EntrypointLookupInterface
    {
        $themePath = Path::join($this->webDir, 'bundles/contaocore/theme/entrypoints.json');

        return new EntrypointLookup($themePath);
    }
}
