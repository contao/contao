<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller;

use Contao\CoreBundle\Controller\InsertTagsController;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\InsertTag\InsertTagParser;
use Contao\CoreBundle\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class InsertTagsControllerTest extends TestCase
{
    public function testRendersInsertTag(): void
    {
        $insertTagResponse = new Response();

        $insertTagParser = $this->createMock(InsertTagParser::class);
        $insertTagParser
            ->method('replaceInlineAsResponse')
            ->with('{{request_token}}')
            ->willReturn($insertTagResponse)
        ;

        $controller = new InsertTagsController($insertTagParser, $this->createMock(ContaoFramework::class), $this->createMock(RequestStack::class));
        $response = $controller->renderAction(new Request(), '{{request_token}}', null);

        $this->assertSame($insertTagResponse, $response);
    }
}
