<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\E2eTests\Backend;

use Contao\E2eTesting\Browser\BackendBrowser;
use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Fixture\FixtureSet;
use OTPHP\TOTP;
use Symfony\Component\Clock\NativeClock;

class TurboFormSubmissionTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testShowsAnErrorIfTheTwoFactorCodeIsInvalid(): void
    {
        $backend = $this->login();
        $backend->visit('/contao?do=security&act=enable');

        $backend->page()->locator('#verify')->fill('000000');
        $backend->clickButton('.tl_two_factor_form button[type="submit"]');

        $this->assertSelectorExists('.two-factor p.tl_error');
    }

    public function testRemovesTheTrustedDevicesFromTheList(): void
    {
        self::managedEdition()->resetDatabase(new FixtureSet([
            self::fixtureDirectory().'/'.self::FIXTURE_USERS,
            self::fixtureDirectory().'/trusted-device.yaml',
        ]));

        $backend = $this->login();
        $this->enableTwoFactor($backend);

        $backend->waitFor('.tl_trusted_devices tbody tr');

        $backend->clickButton('.tl_trusted_devices button[type="submit"]');

        $this->assertSelectorTextContains('.tl_trusted_devices', 'Currently there are no trusted devices.');
    }

    private function enableTwoFactor(BackendBrowser $backend): void
    {
        $backend->visit('/contao?do=security&act=enable');

        $secret = $backend->page()->locator('.tl_two_factor_form code')->textContent();
        $code = TOTP::create($secret, clock: new NativeClock())->now();

        $backend->page()->locator('#verify')->fill($code);
        $backend->waitForNavigation(static fn () => $backend->clickButton('.tl_two_factor_form button[type="submit"]'));
    }
}
