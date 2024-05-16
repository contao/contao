<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Session;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\CoreBundle\Session\SessionFinder;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class SessionFinderTest extends TestCase
{
    /**
     * @dataProvider provideScopeAndSession
     */
    public function testReturnsTheCorrectSession(bool $isBackend, bool $isPopup, string $storageKey, string $name): void
    {
        $scopeMatcher = $this->createMock(ScopeMatcher::class);
        $scopeMatcher
            ->expects($this->once())
            ->method('isBackendRequest')
            ->willReturn($isBackend)
        ;

        $request = Request::createFromGlobals();
        $request->setSession($this->mockSession());

        if ($isPopup) {
            $request->query->set('popup', '1');
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $sessionFinder = new SessionFinder($scopeMatcher, $requestStack);
        $session = $sessionFinder->getSession();

        $this->assertSame($storageKey, $session->getStorageKey());
        $this->assertSame($name, $session->getName());
    }

    public static function provideScopeAndSession(): iterable
    {
        yield [
            true,
            false,
            '_contao_be_attributes',
            'contao_backend',
        ];

        yield [
            true,
            true,
            '_contao_be_popup_attributes',
            'contao_backend_popup',
        ];

        yield [
            false,
            false,
            '_contao_fe_attributes',
            'contao_frontend',
        ];

        yield [
            false,
            true,
            '_contao_fe_attributes',
            'contao_frontend',
        ];
    }
}
