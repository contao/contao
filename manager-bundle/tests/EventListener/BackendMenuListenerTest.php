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

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\Tests\TestCase;
use Contao\ManagerBundle\EventListener\BackendMenuListener;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BackendMenuListenerTest extends TestCase
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ItemInterface
     */
    private $tree;

    /**
     * @var MenuItem|MockObject
     */
    private $systemNode;

    /**
     * @var FactoryInterface|MockObject
     */
    private $factory;

    /**
     * @var \Contao\BackendUser
     */
    private $backendUser;

    protected function setUp()
    {
        parent::setUp();

        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->factory      = $this->createMock(FactoryInterface::class);
        $this->tree         = new MenuItem('root', $this->factory);

        $this->factory->method('createItem')->willReturnCallback(
            function (string $name) {
                return new MenuItem($name, $this->factory);
            }
        );

        $this->systemNode = $this->createPartialMock(MenuItem::class, ['addChild', 'getName']);
        $this->systemNode->method('getName')->willReturn('system');

        $this->backendUser = $this->createPartialMock(BackendUser::class, ['__get']);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($this->backendUser);

        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->tree->addChild($this->factory->createItem('system'));
    }

    public function testContaoManagerBackendNavItemIsAddedForAdminUser(): void
    {
        $listener = new BackendMenuListener($this->tokenStorage, 'contao-manager.phar.php');
        $event    = new MenuEvent($this->factory, $this->tree);

        $this->backendUser->method('__get')->with('isAdmin')->willReturn(true);

        $this->tree->addChild($this->systemNode);

        $this->systemNode
            ->expects($this->once())
            ->method('addChild');

        $listener->onBuild($event);
    }

    public function testContaoManagerBackendNavItemIsNotAddedForNonAdminUser(): void
    {
        $listener = new BackendMenuListener($this->tokenStorage, 'contao-manager.phar.php');
        $event    = new MenuEvent($this->factory, $this->tree);

        $this->backendUser->method('__get')->with('isAdmin')->willReturn(false);

        $this->tree->addChild($this->systemNode);

        $this->systemNode
            ->expects($this->never())
            ->method('addChild');

        $listener->onBuild($event);
    }

    public function testContaoManagerBackendNavItemIsNotAddedForMissingConfig(): void
    {
        $listener = new BackendMenuListener($this->tokenStorage, null);
        $event    = new MenuEvent($this->factory, $this->tree);

        $this->backendUser->method('__get')->with('isAdmin')->willReturn(true);

        $this->tree->addChild($this->systemNode);

        $this->systemNode
            ->expects($this->never())
            ->method('addChild');

        $listener->onBuild($event);
    }
}
