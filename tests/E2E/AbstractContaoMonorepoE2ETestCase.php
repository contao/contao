<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests;

use Contao\E2eTesting\Composer\MonorepoProject;
use Contao\E2eTesting\ManagedEdition\AbstractManagedEditionTestCase;
use Contao\InstallationRecipe\Composer\ComposerConfig;

abstract class AbstractContaoMonorepoE2ETestCase extends AbstractManagedEditionTestCase
{
    /**
     * Personal access token of the k.jones fixture user.
     */
    protected const string BACKEND_ACCESS_TOKEN = 'ct_pat_0000000000E008000000000042_kevinjones';

    protected static function projectDirectory(): string
    {
        return \dirname(__DIR__, 2);
    }

    protected static function createMonorepoComposerConfig(string ...$bundles): ComposerConfig
    {
        $projectDirectory = self::projectDirectory();
        $monorepo = MonorepoProject::discover($projectDirectory);
        $composer = ComposerConfig::managedEdition($monorepo->version)->withOverrides([
            'require' => ['contao/manager-plugin' => 'dev-feature/runtime-defaults as 2.14.0'],
            'repositories' => [[
                'type' => 'vcs',
                'url' => 'https://github.com/Toflar/manager-plugin.git',
            ]],
            'config' => ['allow-plugins' => ['symfony/runtime' => true]],
        ]);

        return $monorepo->configureComposer($composer, 'manager-bundle', ...$bundles);
    }
}
