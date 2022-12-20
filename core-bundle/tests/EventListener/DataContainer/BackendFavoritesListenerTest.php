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

use Contao\BackendUser;
use Contao\CoreBundle\EventListener\DataContainer\BackendFavoritesListener;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class BackendFavoritesListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAllowsAddingNewFavorites(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_favorites'] = [
            'config' => [
                'notCreatable' => true,
            ],
            'list' => [
                'sorting' => [
                    'filter' => [],
                ],
            ],
            'fields' => [
                'url' => [
                    'default' => '',
                ],
                'user' => [
                    'default' => 0,
                ],
            ],
        ];

        $userId = 2;
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => $userId]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $url = '/contao?do=pages&act=edit&id=4';

        $request = new Request();
        $request->query->set('act', 'create');
        $request->query->set('data', base64_encode($url));
        $request->query->set('ref', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->id = $userId;

        $listener = new BackendFavoritesListener($security, $requestStack, $this->createMock(Connection::class));
        $listener($dc);

        $this->assertFalse($GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable']);
        $this->assertSame([['user=?', $userId]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
        $this->assertSame($url, $GLOBALS['TL_DCA']['tl_favorites']['fields']['url']['default']);
        $this->assertSame($userId, $GLOBALS['TL_DCA']['tl_favorites']['fields']['user']['default']);
    }

    public function testDoesNotAllowAddingNewFavoritesIfThereIsNoData(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_favorites'] = [
            'config' => [
                'notCreatable' => true,
            ],
            'list' => [
                'sorting' => [
                    'filter' => [],
                ],
            ],
        ];

        $userId = 2;
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => $userId]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = new Request();
        $request->query->set('act', 'create');
        $request->query->set('ref', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->id = 3;

        $listener = new BackendFavoritesListener($security, $requestStack, $this->createMock(Connection::class));
        $listener($dc);

        $this->assertTrue($GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable']);
        $this->assertSame([['user=?', $userId]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
    }

    public function testShowsNothingIfThereIsNoUser(): void
    {
        /** @var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_favorites'] = [
            'list' => [
                'sorting' => [
                    'filter' => [],
                ],
            ],
        ];

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($this->createMock(UserInterface::class))
        ;

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $dc = $this->mockClassWithProperties(DataContainer::class);

        $listener = new BackendFavoritesListener($security, $requestStack, $this->createMock(Connection::class));
        $listener($dc);

        $this->assertSame([['user=?', 0]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
    }

    public function testDeniesAccessToOtherUsersFavorites(): void
    {
        $GLOBALS['TL_DCA']['tl_favorites'] = [
            'list' => [
                'sorting' => [
                    'filter' => [],
                ],
            ],
        ];

        $userId = 2;
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => $userId]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = new Request();
        $request->query->set('act', 'edit');
        $request->query->set('id', 3);
        $request->query->set('ref', 'foobar');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $dc = $this->mockClassWithProperties(DataContainer::class);
        $dc->id = 3;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchOne')
            ->with('SELECT user FROM tl_favorites WHERE id = :id', ['id' => $dc->id])
            ->willReturn(17)
        ;

        $listener = new BackendFavoritesListener($security, $requestStack, $connection);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage(sprintf('Favorite ID %s does not belong to user ID %s', $dc->id, $userId));

        $listener($dc);
    }

    public function testReplacesTheCurrentIdsInEditMultipleMode(): void
    {
        $GLOBALS['TL_DCA']['tl_favorites'] = [
            'list' => [
                'sorting' => [
                    'filter' => [],
                ],
            ],
        ];

        $userId = 2;
        $user = $this->mockClassWithProperties(BackendUser::class, ['id' => $userId]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $session = $this->mockSession();
        $sessionData['CURRENT']['IDS'] = [2, 3, 6, 7, 8, 9];
        $session->replace($sessionData);

        $request = new Request();
        $request->query->set('act', 'editAll');
        $request->query->set('ref', 'foobar');
        $request->setSession($session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $dc = $this->mockClassWithProperties(DataContainer::class);

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('fetchFirstColumn')
            ->with('SELECT id FROM tl_favorites WHERE user = :userId', ['userId' => $userId])
            ->willReturn([3, 7, 9])
        ;

        $listener = new BackendFavoritesListener($security, $requestStack, $connection);
        $listener($dc);

        $this->assertSame([3, 7, 9], array_values($session->all()['CURRENT']['IDS']));
    }
}
