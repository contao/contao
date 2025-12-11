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
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => $userId]);

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

        $requestStack = new RequestStack([$request]);

        $listener = new BackendFavoritesListener($security, $requestStack);
        $listener->enableEditing();

        $this->assertFalse($GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable']);
        $this->assertSame([['user = ?', $userId]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
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
        $user = $this->createClassWithPropertiesStub(BackendUser::class, ['id' => $userId]);

        $security = $this->createMock(Security::class);
        $security
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user)
        ;

        $request = new Request();
        $request->query->set('act', 'create');

        $requestStack = new RequestStack([$request]);

        $listener = new BackendFavoritesListener($security, $requestStack);
        $listener->enableEditing();

        $this->assertTrue($GLOBALS['TL_DCA']['tl_favorites']['config']['notCreatable']);
        $this->assertSame([['user = ?', $userId]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
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
            ->willReturn($this->createStub(UserInterface::class))
        ;

        $requestStack = new RequestStack([new Request()]);

        $listener = new BackendFavoritesListener($security, $requestStack);
        $listener->enableEditing();

        $this->assertSame([['user = ?', 0]], $GLOBALS['TL_DCA']['tl_favorites']['list']['sorting']['filter']);
    }

    public function testRedirectsBack(): void
    {
        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn([
                'id' => 42,
                'url' => '/contao?foo=bar',
            ])
        ;

        $request = new Request();
        $request->query->set('return', '1');
        $request->request->set('saveNclose', '1');

        $requestStack = new RequestStack([$request]);

        $listener = new BackendFavoritesListener($this->createStub(Security::class), $requestStack);
        $redirect = null;

        try {
            $listener->redirectBack($dataContainer);
        } catch (RedirectResponseException $redirect) {
        }

        $this->assertInstanceOf(RedirectResponseException::class, $redirect);
        $this->assertSame('/contao?foo=bar', $redirect->getResponse()->headers->get('Location'));
    }

    public function testDoesNotRedirectOnSave(): void
    {
        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer
            ->expects($this->never())
            ->method('getCurrentRecord')
        ;

        $request = new Request();
        $request->query->set('return', '1');
        $request->request->set('save', '1');

        $requestStack = new RequestStack([$request]);

        $listener = new BackendFavoritesListener($this->createStub(Security::class), $requestStack);
        $listener->redirectBack($dataContainer);
    }

    public function testDoesNotRedirectWithoutReturnParameter(): void
    {
        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer
            ->expects($this->never())
            ->method('getCurrentRecord')
        ;

        $request = new Request();
        $request->request->set('saveNclose', '1');

        $requestStack = new RequestStack([$request]);

        $listener = new BackendFavoritesListener($this->createStub(Security::class), $requestStack);
        $listener->redirectBack($dataContainer);
    }
}
