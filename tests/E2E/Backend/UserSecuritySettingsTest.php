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

class UserSecuritySettingsTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testShowsNewlyCreatedPersonalAccessToken(): void
    {
        $backend = $this->login();
        $backend->visit('/contao?do=security');

        $this->waitForTurboFrameLoad($backend, static fn () => $backend->page()->getByRole('link', ['name' => 'Generate token', 'exact' => true])->click());

        $backend->page()->locator('[name="pat_name"]')->fill('Created token');
        $backend->page()->locator('[name="pat_expires"]')->selectOption('7');

        $this->waitForTurboFrameLoad($backend, static fn () => $backend->page()->getByRole('button', ['name' => 'Generate token', 'exact' => true])->click());

        $token = $backend->page()->locator('#personal-access-tokens button[data-controller="contao--clipboard"] code');

        $this->assertSame(1, $token->count(), $backend->page()->locator('#personal-access-tokens')->innerText());
        $this->assertStringStartsWith('ct_pat_', $token->innerText());

        // The plaintext token must not be restored from the Turbo snapshot cache
        $backend->clickLink('Pages');
        $backend->waitForNavigation(static fn () => $backend->page()->goBack());

        $this->assertSame(2, $backend->page()->locator('#personal-access-tokens .pat-table tbody tr')->count());
        $this->assertSame(0, $backend->page()->locator('#personal-access-tokens code')->count());
    }

    public function testDoesNotShowCreateFormAgainAfterReload(): void
    {
        $backend = $this->login();

        // Opening the link in a new tab loads the create form without the frame
        $backend->visit('/contao?do=security&act=create_pat');

        $backend->page()->locator('[name="pat_name"]')->fill('Created token');
        $backend->page()->locator('[name="pat_expires"]')->selectOption('7');

        $this->waitForTurboFrameLoad($backend, static fn () => $backend->page()->getByRole('button', ['name' => 'Generate token', 'exact' => true])->click());

        $this->assertSame(1, $backend->page()->locator('#personal-access-tokens button[data-controller="contao--clipboard"] code')->count());

        $backend->waitForNavigation(static fn () => $backend->page()->reload());

        $this->assertSame(0, $backend->page()->locator('#personal-access-tokens [name="pat_name"]')->count(), $backend->page()->url());
        $this->assertSame(0, $backend->page()->locator('#personal-access-tokens code')->count());
    }

    public function testRenamesPasskey(): void
    {
        self::managedEdition()->resetDatabase(new FixtureSet([
            self::fixtureDirectory().'/'.self::FIXTURE_USERS,
            self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT,
            self::fixtureDirectory().'/passkeys.yaml',
        ]));

        $backend = $this->login('passkey_user');
        $backend->visit('/contao?do=security');

        $this->assertSelectorTextContains('#passkeys', 'Old passkey');

        $this->waitForTurboFrameLoad($backend, static fn () => $backend->clickButton('#passkeys button[name="edit_passkey"]'));

        $name = $backend->page()->locator('#passkeys [name="passkey_name"]');

        $this->assertSame(1, $name->count(), $backend->page()->locator('#passkeys')->innerText());

        $name->fill('New passkey');

        $this->waitForTurboFrameLoad($backend, static fn () => $backend->clickButton('#passkeys button[name="save_passkey"]'));

        $this->assertSelectorTextContains('#passkeys', 'New passkey');
    }

    public function testDoesNotEditNewPasskeyAgainAfterReload(): void
    {
        self::managedEdition()->resetDatabase(new FixtureSet([
            self::fixtureDirectory().'/'.self::FIXTURE_USERS,
            self::fixtureDirectory().'/'.self::FIXTURE_DEFAULT,
            self::fixtureDirectory().'/passkeys.yaml',
        ]));

        $backend = $this->login('passkey_user');
        $backend->visit('/contao?do=security');

        $backend->visit($backend->page()->locator('[data-contao--webauthn-registration-success-redirect-uri-value]')->getAttribute('data-contao--webauthn-registration-success-redirect-uri-value'));

        $name = $backend->page()->locator('#passkeys [name="passkey_name"]');

        $this->assertSame(1, $name->count(), $backend->page()->locator('#passkeys')->innerText());

        $name->fill('New passkey');

        $this->waitForTurboFrameLoad($backend, static fn () => $backend->clickButton('#passkeys button[name="save_passkey"]'));

        $backend->waitForNavigation(static fn () => $backend->page()->reload());

        $this->assertSame(0, $backend->page()->locator('#passkeys [name="passkey_name"]')->count(), $backend->page()->url());
        $this->assertSelectorTextContains('#passkeys', 'New passkey');
    }

    private function waitForTurboFrameLoad(BackendBrowser $backend, callable $action): void
    {
        $backend->page()->evaluate('() => { window.turboFrameLoaded = false; document.addEventListener("turbo:frame-load", () => requestAnimationFrame(() => setTimeout(() => window.turboFrameLoaded = true)), { once: true }); }');

        $action();

        $backend->page()->waitForFunction('() => window.turboFrameLoaded');
    }
}
