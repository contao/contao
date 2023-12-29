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

use Contao\CoreBundle\Controller\Page\ErrorPageController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendIndex;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

class ErrorPageControllerTest extends TestCase
{
    public function testRendersThePageThroughFrontendIndex(): void
    {
        $response = $this->createMock(Response::class);
        $pageModel = $this->mockClassWithProperties(PageModel::class);

        $frontendIndex = $this->createMock(FrontendIndex::class);
        $frontendIndex
            ->expects($this->once())
            ->method('renderPage')
            ->with($pageModel)
            ->willReturn($response)
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->once())
            ->method('createInstance')
            ->with(FrontendIndex::class)
            ->willReturn($frontendIndex)
        ;

        $controller = new ErrorPageController($framework);

        $this->assertSame($response, $controller($pageModel));
    }

    public function testSupportsContentComposition(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'error_404',
            'autoforward' => false,
        ]);

        $controller = new ErrorPageController($this->mockContaoFramework());

        $this->assertTrue($controller->supportsContentComposition($pageModel));
    }

    public function testDisablesContentCompositionWithAutoforward(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'error_404',
            'autoforward' => true,
        ]);

        $controller = new ErrorPageController($this->mockContaoFramework());

        $this->assertFalse($controller->supportsContentComposition($pageModel));
    }

    public function testAlwaysSupportsContentCompositionFor503Page(): void
    {
        $pageModel = $this->mockClassWithProperties(PageModel::class, [
            'type' => 'error_503',
            'autoforward' => true,
        ]);

        $controller = new ErrorPageController($this->mockContaoFramework());

        $this->assertTrue($controller->supportsContentComposition($pageModel));
    }
}
