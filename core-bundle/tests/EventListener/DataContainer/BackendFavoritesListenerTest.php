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
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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

        $listener = new BackendFavoritesListener($security, $requestStack);
        $listener();

        $this->assertFalse($GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable']);
        $this->assertSame([['user=?', $userId]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
        $this->assertSame($url, $GLOBALS['TL_DCA']['tl_favorites']['fields']['url']['default']);
        $this->assertSame($userId, $GLOBALS['TL_DCA']['tl_favorites']['fields']['user']['default']);
    }

    public function testDoesNotAllowAddingNewFavoritesIfThereIsNoData(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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

        $listener = new BackendFavoritesListener($security, $requestStack);
        $listener();

        $this->assertTrue($GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable']);
        $this->assertSame([['user=?', $userId]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
    }

    public function testShowsNothingIfThereIsNoUser(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
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

        $listener = new BackendFavoritesListener($security, $requestStack);
        $listener();

        $this->assertSame([['user=?', 0]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
    }
}
