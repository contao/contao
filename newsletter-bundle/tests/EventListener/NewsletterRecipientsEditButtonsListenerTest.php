<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\NewsletterBundle\Tests\EventListener;

use Contao\NewsletterBundle\EventListener\NewsletterRecipientsEditButtonsListener;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class NewsletterRecipientsEditButtonsListenerTest extends ContaoTestCase
{
    #[DataProvider('buttonProvider')]
    public function testRemovesSaveNCloseButton(array $buttons, array $expected): void
    {
        $result = (new NewsletterRecipientsEditButtonsListener())($buttons);

        $this->assertSame($result, $expected);
    }

    public static function buttonProvider(): iterable
    {
        yield 'Removes the saveNduplicate button' => [
            ['save' => true, 'saveNduplicate' => true, 'saveNcreate' => true],
            ['save' => true, 'saveNcreate' => true],
        ];

        yield 'Removes nothing' => [
            ['save' => true, 'saveNedit' => true],
            ['save' => true, 'saveNedit' => true],
        ];
    }
}
