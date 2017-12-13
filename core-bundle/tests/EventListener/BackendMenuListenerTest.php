<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * Copyright (c) 2005-2017 Leo Feyer
 *
 * @license LGPL-3.0+
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\BackendUser;
use Contao\CoreBundle\Event\MenuEvent;
use Contao\CoreBundle\EventListener\BackendMenuListener;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class BackendMenuListenerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $listener = new BackendMenuListener($this->createMock(TokenStorageInterface::class));

        $this->assertInstanceOf('Contao\CoreBundle\EventListener\BackendMenuListener', $listener);
    }

    public function testCreatesANodeListFromTheBackendUserMenuArray(): void
    {
        $user = $this->createPartialMock(BackendUser::class, ['hasAccess', 'navigation']);

        $user
            ->method('hasAccess')
            ->willReturn(true)
        ;

        $user
            ->method('navigation')
            ->willReturn([
                'category1' => [
                    'label' => 'Category 1',
                    'title' => 'Category 1 Title',
                    'href' => '/',
                    'class' => 'node-expanded',
                    'modules' => [
                        'node1' => [
                            'label' => 'Node 1',
                            'title' => 'Node 1 Title',
                            'href' => '/node1',
                            'isActive' => true,
                        ],
                        'node2' => [
                            'label' => 'Node 2',
                            'title' => 'Node 2 Title',
                            'href' => '/node2',
                            'isActive' => false,
                        ],
                    ],
                ],
                'category2' => [
                    'label' => 'Category 2',
                    'title' => 'Category 2 Title',
                    'href' => '/',
                    'class' => 'node-collapsed',
                    'modules' => [],
                ],
            ])
        ;

        $token = $this->createMock(TokenInterface::class);

        $token
            ->method('getUser')
            ->willReturn($user)
        ;

        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn($token)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendMenuListener($tokenStorage);
        $listener->onBuild($event);

        $tree = $event->getTree();

        // Test root node
        $this->assertInstanceOf('Knp\Menu\ItemInterface', $tree);
        $this->assertCount(2, $tree->getChildren());

        // Test category node
        $categoryNode = $tree->getChild('category1');
        $this->assertNotNull($categoryNode);
        $this->assertInstanceOf('Knp\Menu\ItemInterface', $categoryNode);
        $this->assertCount(2, $categoryNode->getChildren());

        // Test module node
        $moduleNode = $categoryNode->getChild('node1');
        $this->assertNotNull($moduleNode);
        $this->assertInstanceOf('Knp\Menu\ItemInterface', $moduleNode);
        $this->assertCount(0, $moduleNode->getChildren());

        // Test expanded/collapsed
        $childNode = $tree->getChild('category1');
        $this->assertNotNull($childNode);
        $this->assertTrue($childNode->getDisplayChildren());

        $childNode = $tree->getChild('category2');
        $this->assertNotNull($childNode);
        $this->assertFalse($childNode->getDisplayChildren());

        // Test active/not active
        $childNode = $categoryNode->getChild('node1');
        $this->assertNotNull($childNode);
        $this->assertTrue($childNode->isCurrent());

        $childNode = $categoryNode->getChild('node2');
        $this->assertNotNull($childNode);
        $this->assertFalse($childNode->isCurrent());
    }

    public function testDoesNotModifyTheTreeIfNoUserOrTokenIsGiven(): void
    {
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        $tokenStorage
            ->method('getToken')
            ->willReturn(null)
        ;

        $nodeFactory = new MenuFactory();
        $event = new MenuEvent($nodeFactory, $nodeFactory->createItem('root'));

        $listener = new BackendMenuListener($tokenStorage);
        $listener->onBuild($event);

        $tree = $event->getTree();

        $this->assertCount(0, $tree->getChildren());
    }
}
