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
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContaoVariableTest extends TestCase
{
    public function testReturnsThePage(): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class);

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
        );

        $this->assertSame($page, $contaoVariable->getPage());
    }

    #[DataProvider('getDateFormats')]
    public function testReturnsPageDatimFormat(string $variable, string $format, string $function): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class, [$variable]);
        $page->{$variable} = $format;

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $contaoFramework = $this->createContaoFrameworkMock();
        $contaoFramework
            ->expects($this->never())
            ->method('initialize')
        ;

        $contaoVariable = new ContaoVariable(
            $requestStack,
            $this->createMock(TokenChecker::class),
            $this->createMock(ContaoCsrfTokenManager::class),
            $contaoFramework,
        );

        $this->assertSame($format, $contaoVariable->{$function}());
    }

    #[DataProvider('getDateFormats')]
    public function testReturnsGlobalDatimFormat(string $variable, string $format, string $function): void
    {
        $page = $this->createClassWithPropertiesStub(PageModel::class, [$variable]);
        $page->{$variable} = '';

        $request = new Request();
        $request->attributes->set('pageModel', $page);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request)
        ;

        $config = $this->createAdapterMock(['get']);
        $config
            ->expects($this->once())
            ->method('get')
            ->with($variable)
            ->willReturn($format)
        ;

        $contaoFramework = $this->createContaoFrameworkMock([Config::class => $config]);
        $contaoFramework
            ->expects($this->once())
            ->method('initialize')
        ;

        $contaoVariable = new ContaoVariable(
            $requestStack,
            $this->createMock(TokenChecker::class),
            $this->createMock(ContaoCsrfTokenManager::class),
            $contaoFramework,
        );

        $this->assertSame($format, $contaoVariable->{$function}());
    }

    public static function getDateFormats(): iterable
    {
        yield ['datimFormat', 'j. M Y, H:i', 'getDatim_format'];
        yield ['dateFormat', 'j. M Y', 'getDate_format'];
        yield ['timeFormat', 'G:i', 'getTime_format'];
    }
}
