<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ManagerBundle\Tests\EventListener;

use Contao\CoreBundle\Event\MenuEvent;
use Contao\ManagerBundle\EventListener\BackendMenuListener;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Security\Core\Security;

class BackendMenuListenerTest extends ContaoTestCase
{
    public function testAddsTheContaoManagerLinkIfTheUserIsAnAdmin(): void
    {
        $listener = $this->getListener(true, 'contao-manager.phar.php');
        $listener($this->getMenuEvent(true));
    }

    public function testDoesNotAddTheContaoManagerLinkIfTheUserIsNotAnAdmin(): void
    {
        $listener = $this->getListener(false, 'contao-manager.phar.php');
        $listener($this->getMenuEvent(false));
    }

    public function testDoesNotAddTheContaoManagerLinkIfTheManagerPathIsNotConfigured(): void
    {
        $listener = $this->getListener(true);
        $listener($this->getMenuEvent(false));
    }

    private function getMenuEvent(bool $addLink): MenuEvent
    {
        $factory = $this->createMock(FactoryInterface::class);

        $systemNode = $this->createPartialMock(MenuItem::class, ['addChild', 'getName']);
        $systemNode
            ->expects($addLink ? $this->once() : $this->never())
            ->method('addChild')
        ;

        $systemNode
            ->method('getName')
            ->willReturn('system')
        ;

        $tree = new MenuItem('root', $factory);
        $tree->addChild($systemNode);

        return new MenuEvent($factory, $tree);
    }

    private function getListener(bool $isAdmin, string $path = null): BackendMenuListener
    {
        $security = $this->createMock(Security::class);
        $security
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn($isAdmin)
        ;

        return new BackendMenuListener($security, $path);
    }
}
