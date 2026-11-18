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

class TurboSnapshotCacheTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testDoesNotShowAConfirmationAgainAfterGoingBack(): void
    {
        $backend = $this->login();
        $backend->visit('/contao?do=login');

        $backend->page()->locator('input[name="purge[]"][value="purge_session"]')->check();
        $backend->submitForm('Save');

        $this->assertSelectorTextContains('.tl_message .tl_confirm', 'session data');

        $this->leaveAndGoBack($backend);

        $this->assertSame(0, $backend->page()->locator('.tl_message .tl_confirm')->count());
    }

    public function testDoesNotAddASecondFileTreeDropZoneAfterGoingBack(): void
    {
        $backend = $this->login();
        $backend->visit('/contao?do=files');
        $backend->waitFor('#tl_listing .dropzone-filetree');

        $this->leaveAndGoBack($backend);

        $this->assertSame(1, $backend->page()->locator('#tl_listing .dropzone-filetree')->count());
    }

    private function leaveAndGoBack(BackendBrowser $backend): void
    {
        $backend->clickLink('Pages');
        $backend->waitForTurboNavigation(static fn () => $backend->page()->goBack());
    }
}
