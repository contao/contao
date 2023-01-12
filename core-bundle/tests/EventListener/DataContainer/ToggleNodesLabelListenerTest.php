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
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class ToggleNodesLabelListenerTest extends TestCase
{
    protected function setUp(): void
    {
        unset($GLOBALS['TL_DCA']);
        $GLOBALS['TL_LANG']['DCA']['expandNodes'] = 'expandNodes';
        $GLOBALS['TL_LANG']['DCA']['collapseNodes'] = 'collapseNodes';
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['TL_DCA'], $GLOBALS['TL_LANG']);
    }

    public function testDoesNothingIfOperationDoesNotExist(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['global_operations'] = [];

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->never())
            ->method('getCurrentRequest')
        ;

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');
    }

    public function testDoesNothingIfOperationHasLabel(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'label' => 'foo',
        ];

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->never())
            ->method('getCurrentRequest')
        ;

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');
    }

    public function testDoesNothingIfOperationHasUnsupportedHref(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'href' => 'foo=bar',
        ];

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->never())
            ->method('getCurrentRequest')
        ;

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');
    }

    public function testDoesNothingIfSessionIsNull(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'href' => 'tg=all',
        ];

        $requestStack = $this->mockRequestStackWithSession(null);

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');
    }

    public function testSetsExpandLabelIfSessionIsEmpty(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['list']['sorting']['mode'] = 5;
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'href' => 'tg=all',
        ];

        $session = $this->mockSessionWithData([]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');

        /** @var array $GLOBALS */
        $this->assertSame('expandNodes', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsExpandLabelIfSessionIsNotAnArray(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['list']['sorting']['mode'] = 5;
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'href' => 'tg=all',
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tree' => 'foo']);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');

        /** @var array $GLOBALS */
        $this->assertSame('expandNodes', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsExpandLabelIfSessionIsNot1(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['list']['sorting']['mode'] = 5;
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'href' => 'tg=all',
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tree' => [0]]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');

        /** @var array $GLOBALS */
        $this->assertSame('expandNodes', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsCollapseLabelIfSessionIs1(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['list']['sorting']['mode'] = 5;
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'href' => 'tg=all',
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tree' => [1]]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');

        /** @var array $GLOBALS */
        $this->assertSame('collapseNodes', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    public function testSetsCollapseLabelIfSessionIs1InMode6(): void
    {
        $GLOBALS['TL_DCA']['tl_foobar']['config']['ptable'] = 'tl_bar';
        $GLOBALS['TL_DCA']['tl_foobar']['list']['sorting']['mode'] = 6;
        $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes'] = [
            'href' => 'tg=all',
        ];

        $session = $this->mockSessionWithData(['tl_foobar_tl_bar_tree' => [1]]);
        $requestStack = $this->mockRequestStackWithSession($session);

        $listener = new ToggleNodesLabelListener($requestStack);
        $listener('tl_foobar');

        /** @var array $GLOBALS */
        $this->assertSame('collapseNodes', $GLOBALS['TL_DCA']['tl_foobar']['list']['global_operations']['toggleNodes']['label']);
    }

    private function mockRequestStackWithSession(?Session $session): RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);

        if (null === $session) {
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

    private function mockSessionWithData(array $data): Session
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
