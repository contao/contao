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

use Contao\E2eTestBundle\Composer\MonorepoProject;
use Contao\E2eTestBundle\ManagedEdition\ManagedEditionTestTrait;
use Contao\InstallationRecipe\Composer\ComposerConfig;
use PHPUnit\Framework\TestCase;

abstract class AbstractContaoMonorepoE2ETestCase extends TestCase
{
    use ManagedEditionTestTrait;

    protected static function projectDirectory(): string
    {
        return \dirname(__DIR__, 2);
    }

    protected static function createMonorepoComposerConfig(string ...$bundles): ComposerConfig
    {
        $projectDirectory = self::projectDirectory();
        $monorepo = MonorepoProject::discover($projectDirectory);
        $composer = ComposerConfig::managedEdition($monorepo->version);

        return $monorepo->configureComposer($composer, 'manager-bundle', ...$bundles);
    }
}
