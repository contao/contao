<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests;

use Contao\DC_Table;
use Contao\TestCase\ContaoTestCase;

class NewsletterFailuresTest extends ContaoTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']['tl_newsletter_failures']);

        parent::tearDown();
    }

    public function testEscapesFailureMessagesInBackend(): void
    {
        require __DIR__.'/../contao/dca/tl_newsletter_failures.php';

        $dataContainer = (new \ReflectionClass(DC_Table::class))->newInstanceWithoutConstructor();
        $record = ['tstamp' => 0, 'email' => 'recipient@example.com', 'error' => '550 5.1.1 Unknown recipient <script>alert(1)</script>'];
        $escapedError = '550 5.1.1 Unknown recipient &lt;script&gt;alert(1)&lt;/script&gt;';

        $this->assertSame(['-', 'recipient@example.com', $escapedError], $dataContainer->generateRecordLabel($record, 'tl_newsletter_failures'));

        $data = ['tl_newsletter_failures' => [['Error' => $record['error']]]];

        foreach ($GLOBALS['TL_DCA']['tl_newsletter_failures']['config']['onshow_callback'] as $callback) {
            $data = $callback($data, $record, $dataContainer);
        }

        $this->assertSame(['tl_newsletter_failures' => [['Error' => $escapedError]]], $data);
    }
}
