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

use Contao\CoreBundle\Controller\Page\Error4xxPageController;
use Contao\CoreBundle\Tests\TestCase;
use Contao\FrontendIndex;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Response;

class Error4xxPageControllerTest extends TestCase
{
    public function testRendersThePageThroughFrontendIndex(): void
    {
        $response = $this->createMock(Response::class);
        $pageModel = $this->mockClassWithProperties(PageModel::class, []);

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

        $controller = new Error4xxPageController($framework);

        $this->assertSame($response, $controller($pageModel));
    }
}
