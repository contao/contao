<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Routing\ResponseContext;

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadManager\HtmlHeadManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\WebpageResponseContext;
use PHPUnit\Framework\TestCase;

class WebpageResponseContextTest extends TestCase
{
    public function testResponseContext(): void
    {
        $context = new WebpageResponseContext($this->createMock(ResponseContext::class), new HtmlHeadManager());

        $this->assertInstanceOf(HtmlHeadManager::class, $context->getHtmlHeadManager());
    }
}
