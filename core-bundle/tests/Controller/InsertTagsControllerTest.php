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
use Symfony\Component\HttpKernel\HttpKernelInterface;

class InsertTagsControllerTest extends TestCase
{
    /**
     * @todo remove/replace this test
     */
    public function testRendersInsertTag(): void
    {
        $controller = new InsertTagsController($this->createMock(InsertTagParser::class), $this->createMock(ContaoFramework::class), $this->createMock(HttpKernelInterface::class));
        $response = $controller->renderAction(new Request(), '{{request_token}}', null);

        $this->assertFalse($response->getContent());
    }
}
