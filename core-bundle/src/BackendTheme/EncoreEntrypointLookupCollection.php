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
    private ContaoFramework $framework;

    public function __construct(BackendThemes $backendThemes, string $projectDir, array $backendConfig, ContaoFramework $framework)
    {
        $this->backendThemes = $backendThemes;
        $this->projectDir = $projectDir;
        $this->backendConfig = $backendConfig;
        $this->framework = $framework;
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
        // A custom theme path is defined in the app config (via contao.backend.theme_path)
        if ($themePath = $this->backendConfig['theme_path']) {
            return new EntrypointLookup($themePath);
        }

        // A global theme is set
        if ($themeName = $this->backendConfig['theme']) {
            $buildName = $themeName;
        }

        // The user has a theme defined
        $user = $this->framework->createInstance(BackendUser::class);

        if (($themeName = $user->backendTheme) && \in_array($themeName, $this->backendThemes->getThemeNames(), true)) {
            $buildName = $themeName;
        }

        // Use the default theme
        if (null === $buildName || '_default' === $buildName) {
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
