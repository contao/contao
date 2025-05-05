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

use Contao\CoreBundle\EventListener\DataContainer\MemberGroupsListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class MemberGroupsListenerTest extends TestCase
{
    public function testAddsTheGuestsGroup(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with('SELECT id, name FROM tl_member_group WHERE tstamp > 0 ORDER BY name')
            ->willReturn([['id' => 1, 'name' => 'Group 1']])
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('MSC.guests', [], 'contao_default')
            ->willReturn('Guests')
        ;

        $listener = new MemberGroupsListener($connection, $translator);

        $this->assertSame([-1 => 'Guests', 1 => 'Group 1'], $listener());
    }
}
