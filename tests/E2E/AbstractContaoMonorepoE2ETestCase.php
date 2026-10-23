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

use Contao\CoreBundle\Security\AccessToken\AccessTokenManager;
use Contao\E2eTesting\Composer\MonorepoProject;
use Contao\E2eTesting\ManagedEdition\AbstractManagedEditionTestCase;
use Contao\InstallationRecipe\Composer\ComposerConfig;
use Symfony\Component\Uid\Uuid;

abstract class AbstractContaoMonorepoE2ETestCase extends AbstractManagedEditionTestCase
{
    protected static function backendPassword(string $userFixture): string
    {
        $hash = self::managedEdition()->database()->fixtures()->value($userFixture, 'password');
        self::assertTrue(password_verify('backend', $hash), 'The backend fixture password must be "backend".');

        return 'backend';
    }

    protected static function backendAccessToken(string $userFixture): string
    {
        $id = self::managedEdition()->database()->fixtures()->value($userFixture.'_token', 'id');

        // Fixture tokens share the test-only secret "e2e". Their identities come from
        // the loaded fixtures.
        return AccessTokenManager::TOKEN_PREFIX.Uuid::fromBinary($id)->toBase32().'_e2e';
    }

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
