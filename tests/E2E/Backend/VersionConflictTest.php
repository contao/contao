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

class VersionConflictTest extends AbstractContaoMonorepoE2ETestCase
{
    use BackendTestTrait;

    public function testShowsTheVersionConflictIfTheRecordHasBeenSavedInTheMeantime(): void
    {
        $database = self::managedEdition()->database();
        $fixtures = $database->fixtures();

        $backend = $this->login();
        $backend->visit($fixtures->interpolate('/contao?do=article&act=edit&id={article_main_home}'));

        // Somebody else saves the article while the edit form is open
        $database->connection()->executeStatement(
            "UPDATE tl_version SET version = version + 1 WHERE fromTable = 'tl_article' AND pid = ?",
            [$fixtures->value('article_main_home')],
        );

        // The author is mandatory and the conflict page is not a Turbo visit, so
        // there is no navigation to wait for
        $backend->select('author', (string) $fixtures->value('kevin_jones'));
        $backend->page()->getByRole('button', ['name' => 'Save', 'exact' => true])->click();

        $this->assertSelectorTextContains('h1', 'Version conflict');
    }
}
