<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\Page;

use Contao\CoreBundle\Controller\Page\RedirectPageController;
use Contao\CoreBundle\Routing\Page\PageRoute;
use Contao\CoreBundle\Routing\RedirectRoute;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\PageModel;
use Contao\StringUtil;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class RedirectPageControllerTest extends TestCase
{
    public function testAddsTheTargetUrlToThePageRoute(): void
    {
        $insertTags = $this->createMock(InsertTags::class);
        $insertTags
            ->method('replace')
            ->willReturnArgument(0)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(InsertTags::class)
            ->willReturn($insertTags)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap(
                [
                    ['contao.framework', $framework],
                ]
            )
        ;

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['url' => 'https://example.com']);

        $controller = new RedirectPageController();
        $controller->setContainer($container);

        $route = $controller->enhancePageRoute(new PageRoute($pageModel));

        $this->assertTrue($route->hasOption(RedirectRoute::TARGET_URL));
        $this->assertSame('https://example.com', $route->getOption(RedirectRoute::TARGET_URL));
    }

    public function testReplacesInsertTagsOnTargetUrl(): void
    {
        $insertTags = $this->createMock(InsertTags::class);
        $insertTags
            ->expects($this->once())
            ->method('replace')
            ->with('{some-insert-tag}')
            ->willReturn('https://example.com')
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(InsertTags::class)
            ->willReturn($insertTags)
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap(
                [
                    ['contao.framework', $framework],
                ]
            )
        ;

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['url' => '{some-insert-tag}']);

        $controller = new RedirectPageController();
        $controller->setContainer($container);

        $route = $controller->enhancePageRoute(new PageRoute($pageModel));

        $this->assertTrue($route->hasOption(RedirectRoute::TARGET_URL));
        $this->assertSame('https://example.com', $route->getOption(RedirectRoute::TARGET_URL));
    }

    public function testEncodesEmailOnTargetUrl(): void
    {
        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method($this->anything())
        ;

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnMap(
                [
                    ['contao.framework', $framework],
                ]
            )
        ;

        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class, ['url' => 'mailto:test@example.org']);

        $controller = new RedirectPageController();
        $controller->setContainer($container);

        $route = $controller->enhancePageRoute(new PageRoute($pageModel));

        $this->assertTrue($route->hasOption(RedirectRoute::TARGET_URL));
        $this->assertSame(StringUtil::encodeEmail('mailto:test@example.org'), $route->getOption(RedirectRoute::TARGET_URL));
    }

    public function testDoesNotAddUrlSuffixes(): void
    {
        $controller = new RedirectPageController();

        $this->assertSame([], $controller->getUrlSuffixes());
    }
}
