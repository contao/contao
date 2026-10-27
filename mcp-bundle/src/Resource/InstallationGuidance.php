<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Resource;

use Mcp\Capability\Attribute\McpResource;

final class InstallationGuidance
{
    #[McpResource(
        uri: 'contao://installation-guidance',
        name: 'contao_installation_guidance',
        title: 'Contao installation and version guidance',
        description: 'Explains the Managed Edition, the relationship between manager-bundle and core-bundle, and how to determine installed package versions.',
        mimeType: 'text/markdown',
    )]
    public function installationGuidance(): string
    {
        return <<<'MARKDOWN'
            # Contao installation and version guidance

            ## Managed Edition

            In the Contao Managed Edition, the root project's composer.json requires `contao/manager-bundle`. This bundle provides the managed application setup and requires `contao/core-bundle` at `self.version`, keeping their installed versions aligned. The root project's own package version is not the Contao version.

            An installation without `contao/manager-bundle` can integrate `contao/core-bundle` into a custom Symfony application. The absence of the manager bundle does not mean Contao is missing.

            ## Determining the installed version

            - Use `composer_package_version` with an exact Composer package name to read installed runtime metadata.
            - Query `contao/manager-bundle` to identify the Managed Edition version. Its version also identifies the core version because the two packages are kept in sync.
            - Query `contao/core-bundle` for the installed core version, including installations without the manager bundle.
            - In the Contao development monorepo, `contao/contao` replaces the individual bundles. A replaced package can have null version fields. Query `contao/contao` when `contao/core-bundle` has no exact installed version.
            - Use `prettyVersion` for display. A requirement such as `^6.1` in composer.json is an allowed version range, not the installed version.
            - Query extension packages individually when their versions matter. Their versions need not match the Contao core version.
            MARKDOWN;
    }
}
