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

use Symfony\WebpackEncoreBundle\Asset\EntrypointLookup;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupInterface;
use Symfony\WebpackEncoreBundle\Exception\UndefinedBuildException;

/**
 * Aggregate the different entry points configured in the container.
 *
 * Retrieve the EntrypointLookup instance from the given key.
 *
 * @internal
 */
class EncoreEntrypointLookupCollection implements EntrypointLookupCollectionInterface
{
    private BackendThemes $backendThemes;
    private string $projectDir;
    private array $backendConfig;

    public function __construct(BackendThemes $backendThemes, string $projectDir, array $backendConfig)
    {
        $this->backendThemes = $backendThemes;
        $this->projectDir = $projectDir;
        $this->backendConfig = $backendConfig;
    }

    /**
     * Returns the correct entrypoint lookup.
     * Defaults to the entrypoints.json in the Contao core backend theme but can be overiden
     * as per app configuration or user settings.
     *
     * @throws UndefinedBuildException if the backend theme does not exist
     */
    public function getEntrypointLookup(string $buildName = null): EntrypointLookupInterface
    {
        if (null === $buildName) {
            $buildName = 'contao';
        }

        // A custom theme path is defined in the app config (via contao.backend.theme_path)
        if ($themePath = $this->backendConfig['theme_path']) {
            return new EntrypointLookup($themePath);
        }

        // Use the default theme
        if ('contao' === $buildName) {
            $themePath = sprintf('%s/web/bundles/contaocore/theme/entrypoints.json', $this->projectDir);

            return new EntrypointLookup($themePath);
        }

        // Use a custom theme
        if (null === $theme = $this->backendThemes->getTheme($buildName)) {
            throw new UndefinedBuildException(sprintf('The backend theme "%s" is not configured', $buildName));
        }

        $themePath = sprintf('%s/%s', $this->projectDir, $theme->getThemePath());

        return new EntrypointLookup($themePath);
    }
}
