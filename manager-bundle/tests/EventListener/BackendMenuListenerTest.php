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
use Contao\ManagerBundle\EventListener\BackendMenuListener;
use Contao\TestCase\ContaoTestCase;
use Knp\Menu\FactoryInterface;
use Knp\Menu\MenuItem;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BackendMenuListenerTest extends ContaoTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $fs = new Filesystem();
        $fs->dumpFile($this->getTempDir().'/web/contao-manager.phar.php', '');
    }

    public function testAddsTheContaoManagerLinkIfTheUserIsAnAdmin(): void
    {
        $listener = new BackendMenuListener(
            $this->getTokenStorage(true),
            $this->getTempDir().'/web',
            'contao-manager.phar.php'
        );

        $listener->onBuild($this->getMenuEvent(true));
    }

    public function testDoesNotAddTheContaoManagerLinkIfTheUserIsNotAnAdmin(): void
    {
        $listener = new BackendMenuListener(
            $this->getTokenStorage(false),
            $this->getTempDir().'/web',
            'contao-manager.phar.php'
        );

        $listener->onBuild($this->getMenuEvent(false));
    }

    public function testDoesNotAddTheContaoManagerLinkIfTheManagerPathIsNotConfigured(): void
    {
        $listener = new BackendMenuListener(
            $this->getTokenStorage(true),
            $this->getTempDir().'/web',
            ''
        );

        $listener->onBuild($this->getMenuEvent(false));
    }

    public function testDoesNotAddTheContaoManagerLinkIfTheFileDoesNotExist(): void
    {
        $listener = new BackendMenuListener(
            $this->getTokenStorage(true),
            $this->getTempDir().'/web',
            'custom.phar.php'
        );

        $listener->onBuild($this->getMenuEvent(false));
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

    private function getTokenStorage(bool $isAdmin): TokenStorageInterface
    {
        $token = $this->createMock(TokenInterface::class);
        $token
            ->method('getUser')
            ->willReturn($this->mockClassWithProperties(BackendUser::class, compact('isAdmin')))
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        return $tokenStorage;
    }
}
