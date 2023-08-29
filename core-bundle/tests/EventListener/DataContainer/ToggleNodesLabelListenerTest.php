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

use Contao\CoreBundle\EventListener\DataContainer\ToggleNodesLabelListener;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ToggleNodesLabelListenerTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['TL_LANG']['DCA'] = [
            'expandNodes' => 'Expand all',
            'collapseNodes' => 'Collapse all',
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG']);
    }

    public function testDoesNothingIfNotBackendRequest(): void
    {
        $requestStack = $this->mockRequestStack();
        $requestStack
            ->expects($this->never())
            ->method('getSession')
        ;

        $listener = new ToggleNodesLabelListener($this->mockRequestStack(), $this->mockScopeMatcher());
        $listener('tl_foobar');
    }

    public function testDoesNothingIfTheOperationDoesNotExist(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'global_operations' => [],
        ];

        $requestStack = $this->mockRequestStack('backend');
        $requestStack
            ->expects($this->never())
            ->method('getSession')
        ;

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');
    }

    public function testDoesNothingIfTheOperationHasALabel(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'list' => [
                'global_operations' => [
                    'toggleNodes' => [
                        'label' => 'Foo',
                    ],
                ],
            ],
        ];

        $requestStack = $this->mockRequestStack('backend');
        $requestStack
            ->expects($this->never())
            ->method('getSession')
        ;

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');
    }

    public function testDoesNothingIfTheOperationHasAnUnsupportedHref(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'list' => [
                'global_operations' => [
                    'toggleNodes' => [
                        'href' => 'foo=bar',
                    ],
                ],
            ],
        ];

        $requestStack = $this->mockRequestStack('backend');
        $requestStack
            ->expects($this->never())
            ->method('getSession')
        ;

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');
    }

    public function testDoesNothingIfThereIsNoSession(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'list' => [
                'global_operations' => [
                    'toggleNodes' => [
                        'href' => 'tg=all',
                    ],
                ],
            ],
        ];

        $requestStack = $this->mockRequestStackWithSession();

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');
    }

    public function testSetsTheExpandLabelIfTheSessionIsEmpty(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'list' => [
                'sorting' => [
                    'mode' => 5,
                ],
                'global_operations' => [
                    'toggleNodes' => [
                        'href' => 'tg=all',
                    ],
                ],
            ],
        ];

        $session = $this->mockSessionWithData([]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');

        $this->assertSame('Expand all', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsTheExpandLabelIfTheSessionIsNotAnArray(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'list' => [
                'sorting' => [
                    'mode' => 5,
                ],
                'global_operations' => [
                    'toggleNodes' => [
                        'href' => 'tg=all',
                    ],
                ],
            ],
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tree' => 'foo']);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');

        $this->assertSame('Expand all', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsTheExpandLabelIfAllNodesAreClosed(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'list' => [
                'sorting' => [
                    'mode' => 5,
                ],
                'global_operations' => [
                    'toggleNodes' => [
                        'href' => 'tg=all',
                    ],
                ],
            ],
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tree' => [0]]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');

        $this->assertSame('Expand all', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsTheCollapseLabelIfThereIsAnExpandedNode(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'list' => [
                'sorting' => [
                    'mode' => 5,
                ],
                'global_operations' => [
                    'toggleNodes' => [
                        'href' => 'tg=all',
                    ],
                ],
            ],
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tree' => [1]]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');

        $this->assertSame('Collapse all', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsTheCollapseLabelIfThereIsAnExpandedNodeInTheExtendedTreeView(): void
    {
        /** @phpstan-var array $GLOBALS (signals PHPStan that the array shape may change) */
        $GLOBALS['TL_DCA']['tl_foobar'] = [
            'config' => [
                'ptable' => 'tl_bar',
            ],
            'list' => [
                'sorting' => [
                    'mode' => 6,
                ],
                'global_operations' => [
                    'toggleNodes' => [
                        'href' => 'tg=all',
                    ],
                ],
            ],
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tl_bar_tree' => [1]]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack, $this->mockScopeMatcher());
        $listener('tl_foobar');

        $this->assertSame('Collapse all', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    private function mockRequestStackWithSession(Session|null $session = null): RequestStack&MockObject
    {
        $requestStack = $this->mockRequestStack('backend');

        if (!$session) {
            $requestStack
                ->expects($this->once())
                ->method('getSession')
                ->willThrowException(new SessionNotFoundException())
            ;
        } else {
            $requestStack
                ->expects($this->once())
                ->method('getSession')
                ->willReturn($session)
            ;
        }

        return $requestStack;
    }

    private function mockRequestStack(string|null $scope = null): RequestStack&MockObject
    {
        $attributes = [];

        if ($scope) {
            $attributes['_scope'] = $scope;
        }

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->method('getCurrentRequest')
            ->willReturn(new Request([], [], $attributes))
        ;

        return $requestStack;
    }

    private function mockSessionWithData(array $data): Session&MockObject
    {
        $sessionBag = $this->createMock(AttributeBagInterface::class);
        $sessionBag
            ->expects($this->once())
            ->method('all')
            ->willReturn($data)
        ;

        $session = $this->createMock(Session::class);
        $session
            ->expects($this->once())
            ->method('getBag')
            ->with('contao_backend')
            ->willReturn($sessionBag)
        ;

        return $session;
    }
}
