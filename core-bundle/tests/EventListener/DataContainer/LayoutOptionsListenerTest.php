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

use Contao\CoreBundle\EventListener\DataContainer\LayoutOptionsListener;
use Contao\CoreBundle\Tests\TestCase;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Service\ResetInterface;

class LayoutOptionsListenerTest extends TestCase
{
    public function testGetsLayoutsOrderedByThemes(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with('SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id ORDER BY t.name, l.name')
            ->willReturn([
                ['id' => 1, 'name' => 'Layout 1', 'theme' => 'Theme A'],
                ['id' => 2, 'name' => 'Layout 2', 'theme' => 'Theme A'],
                ['id' => 3, 'name' => 'Layout 3', 'theme' => 'Theme B'],
            ])
        ;

        $listener = new LayoutOptionsListener($connection);

        $this->assertSame(
            [
                'Theme A' => [
                    1 => 'Layout 1',
                    2 => 'Layout 2',
                ],
                'Theme B' => [
                    3 => 'Layout 3',
                ],
            ],
            $listener(),
        );
    }

    public function testCachesTheLayoutOptions(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->with('SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id ORDER BY t.name, l.name')
            ->willReturn([
                ['id' => 1, 'name' => 'Layout 1', 'theme' => 'Theme A'],
            ])
        ;

        $listener = new LayoutOptionsListener($connection);

        $this->assertSame(['Theme A' => [1 => 'Layout 1']], $listener());
        $this->assertSame(['Theme A' => [1 => 'Layout 1']], $listener());
    }

    public function testServiceIsResetable(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('fetchAllAssociative')
            ->with('SELECT l.id, l.name, t.name AS theme FROM tl_layout l LEFT JOIN tl_theme t ON l.pid=t.id ORDER BY t.name, l.name')
            ->willReturnOnConsecutiveCalls(
                [
                    ['id' => 1, 'name' => 'Layout 1', 'theme' => 'Theme A'],
                ],
                [
                    ['id' => 2, 'name' => 'Layout 2', 'theme' => 'Theme A'],
                ],
            )
        ;

        $listener = new LayoutOptionsListener($connection);

        $this->assertInstanceOf(ResetInterface::class, $listener);

        $this->assertSame(['Theme A' => [1 => 'Layout 1']], $listener());
        $this->assertSame(['Theme A' => [1 => 'Layout 1']], $listener());

        $listener->reset();

        $this->assertSame(['Theme A' => [2 => 'Layout 2']], $listener());
    }
}
