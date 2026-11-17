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

use Contao\E2eTests\AbstractContaoMonorepoE2ETestCase;
use Contao\InstallationRecipe\Fixture\FixtureSet;
use PHPUnit\Framework\Attributes\DataProvider;
use Playwright\Dialog\DialogInterface;

class NewsletterTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    /**
     * @see https://github.com/contao/contao/issues/10575
     */
    #[DataProvider('mailsPerCycleProvider')]
    public function testSendsTheNewsletterOnlyOnce(int $mailsPerCycle): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::fixtureDirectory().'/users.yaml',
            self::fixtureDirectory().'/default.yaml',
            self::fixtureDirectory().'/newsletter.yaml',
        ]));

        $backend = $this->login();
        $backend->visit($fixtures->interpolate('/contao?do=newsletter&table=tl_newsletter&key=send&id={newsletter_foo}'));

        $page = $backend->page();
        $page->locator('#ctrl_mpc')->fill((string) $mailsPerCycle);

        // Confirm the dialog
        $page->events()->onDialog(static fn (DialogInterface $dialog) => $dialog->accept());

        $backend->clickButton('#send');
        $backend->waitFor('.tl_confirm');

        $this->assertSame(11, $this->countSentMails());
    }

    public function testSendsThePreviewOnlyOnce(): void
    {
        $fixtures = self::managedEdition()->resetDatabase(new FixtureSet([
            self::fixtureDirectory().'/users.yaml',
            self::fixtureDirectory().'/default.yaml',
            self::fixtureDirectory().'/newsletter.yaml',
        ]));

        $backend = $this->login();
        $backend->visit($fixtures->interpolate('/contao?do=newsletter&table=tl_newsletter&key=send&id={newsletter_foo}'));

        $backend->clickButton('button[name="preview"]');
        $backend->waitFor('.tl_confirm');

        $this->assertSame(1, $this->countSentMails());
    }

    public static function mailsPerCycleProvider(): iterable
    {
        yield 'default' => [10];
        yield 'custom' => [5];
    }

    private function countSentMails(): int
    {
        return (int) self::managedEdition()
            ->database()
            ->connection()
            ->fetchOne('SELECT COUNT(*) FROM tl_log WHERE text LIKE ?', ['An e-mail has been sent to %'])
        ;
    }
}
