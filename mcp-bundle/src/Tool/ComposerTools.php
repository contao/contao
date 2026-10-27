<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\McpBundle\Tool;

use Composer\InstalledVersions;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

final class ComposerTools
{
    #[McpTool(name: 'composer_package_version', description: 'Get the installed version of an exact Composer package name (vendor/package) using Composer runtime metadata. Returns the normalized version and prettyVersion for display. Both can be null for packages that are only provided or replaced. Unknown packages produce an error.', annotations: new ToolAnnotations(readOnlyHint: true, openWorldHint: false))]
    public function version(string $package): array
    {
        try {
            return [
                'package' => $package,
                'version' => InstalledVersions::getVersion($package),
                'prettyVersion' => InstalledVersions::getPrettyVersion($package),
            ];
        } catch (\OutOfBoundsException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }
    }
}
