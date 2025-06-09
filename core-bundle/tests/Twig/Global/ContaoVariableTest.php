<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Global;

use Contao\Config;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\PageModel;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoVariableTest extends TestCase
{
    public function testReturnsThePage(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class);

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $contaoVariable = new ContaoVariable(
            $requestStack,
            $this->createMock(TokenChecker::class),
            $this->createMock(ContaoCsrfTokenManager::class),
            $this->createMock(ContaoFramework::class),
            $this->createMock(Security::class),
        );

        $this->assertSame($page, $contaoVariable->getPage());
    }

    public function testReturnsPageDatimFormat(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['datimFormat']);
        $page->datimFormat = 'j. M Y, H:i';

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $contaoVariable = new ContaoVariable(
            $requestStack,
            $this->createMock(TokenChecker::class),
            $this->createMock(ContaoCsrfTokenManager::class),
            $this->createMock(ContaoFramework::class),
            $this->createMock(Security::class),
        );

        $this->assertSame('j. M Y, H:i', $contaoVariable->getDatim_format());
    }

    public function testReturnsGlobalDatimFormat(): void
    {
        $page = $this->mockClassWithProperties(PageModel::class, ['datimFormat']);
        $page->datimFormat = '';

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $config = $this->mockAdapter(['get']);
        $config
            ->expects($this->once())
            ->method('get')
            ->with('datimFormat')
            ->willReturn('Y-m-d H:i')
        ;

        $contaoFramework = $this->mockContaoFramework([Config::class => $config]);

        $contaoVariable = new ContaoVariable(
            $requestStack,
            $this->createMock(TokenChecker::class),
            $this->createMock(ContaoCsrfTokenManager::class),
            $contaoFramework,
            $this->createMock(Security::class),
        );

        $this->assertSame('Y-m-d H:i', $contaoVariable->getDatim_format());
    }
}
