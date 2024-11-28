<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\NewsletterRecipientsCopyListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class NewsLetterRecipientsCopyListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testDoesNotAddDoNotCopyIfNotCopyRequest(): void
    {
        $request = Request::create('https://example.com/contao', 'GET', ['act' => 'paste']);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('fetchOne')
        ;

        (new NewsletterRecipientsCopyListener($requestStack, $connection))();

        $this->assertFalse($GLOBALS['TL_DCA']['tl_newsletter_recipient']['fields']['email']['eval']['doNotCopy'] ?? false);
    }

    public function testDoesNotAddDoNotCopyIfTargetPidIsDifferent(): void
    {
        $request = Request::create(
            'https://example.com/contao',
            'GET',
            [
                'act' => 'copy',
                'id' => 1,
                'pid' => 2,
                'mode' => 2,
            ],
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchOne')
            ->withConsecutive(
                ['SELECT email FROM tl_newsletter_recipients WHERE id = ?', [1]],
                ['SELECT TRUE FROM tl_newsletter_recipients WHERE email = ? AND pid = ?', ['foobar@example.com', 2]],
            )
            ->willReturnOnConsecutiveCalls(
                'foobar@example.com',
                false,
            )
        ;

        (new NewsletterRecipientsCopyListener($requestStack, $connection))();

        $this->assertFalse($GLOBALS['TL_DCA']['tl_newsletter_recipient']['fields']['email']['eval']['doNotCopy'] ?? false);
    }

    public function testAddsDoNotCopy(): void
    {
        $request = Request::create(
            'https://example.com/contao',
            'GET',
            [
                'act' => 'copy',
                'id' => 1,
                'pid' => 2,
                'mode' => 2,
            ],
        );

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchOne')
            ->withConsecutive(
                ['SELECT email FROM tl_newsletter_recipients WHERE id = ?', [1]],
                ['SELECT TRUE FROM tl_newsletter_recipients WHERE email = ? AND pid = ?', ['foobar@example.com', 2]],
            )
            ->willReturnOnConsecutiveCalls(
                'foobar@example.com',
                true,
            )
        ;

        (new NewsletterRecipientsCopyListener($requestStack, $connection))();

        $this->assertTrue($GLOBALS['TL_DCA']['tl_newsletter_recipients']['fields']['email']['eval']['doNotCopy'] ?? false);
    }
}
