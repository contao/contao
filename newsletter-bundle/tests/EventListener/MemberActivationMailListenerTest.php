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

use Contao\CoreBundle\Event\MemberActivationMailEvent;
use Contao\CoreBundle\OptIn\OptInToken;
use Contao\MemberModel;
use Contao\NewsletterBundle\EventListener\MemberActivationMailListener;
use Contao\TestCase\ContaoTestCase;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

class MemberActivationMailListenerTest extends ContaoTestCase
{
    public function testAddsChannelsTokenIfNewsletterSelected(): void
    {
        $member = $this->mockClassWithProperties(MemberModel::class, ['newsletter' => serialize([2, 3])]);
        $event = new MemberActivationMailEvent($member, $this->createMock(OptInToken::class), 'subject', 'text', []);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT title FROM tl_newsletter_channel WHERE id IN (?)', [[2, 3]], [Types::SIMPLE_ARRAY])
            ->willReturn(['Channel 1', 'Channel 2'])
        ;

        (new MemberActivationMailListener($connection))($event);

        $this->assertSame(['channels' => "Channel 1\nChannel 2"], $event->getSimpleTokens());
    }

    public function testDoesNotAddChannelsTokenIfNoNewsletterSelected(): void
    {
        $member = $this->mockClassWithProperties(MemberModel::class, ['newsletter' => null]);
        $event = new MemberActivationMailEvent($member, $this->createMock(OptInToken::class), 'subject', 'text', []);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->never())
            ->method('fetchFirstColumn')
        ;

        (new MemberActivationMailListener($connection))($event);

        $this->assertSame([], $event->getSimpleTokens());
    }
}
