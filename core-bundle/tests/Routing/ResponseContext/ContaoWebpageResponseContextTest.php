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

use Contao\CoreBundle\Routing\ResponseContext\ContaoWebpageResponseContext;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadManager\HtmlHeadManager;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextInterface;
use Contao\CoreBundle\Routing\ResponseContext\WebpageResponseContext;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class ContaoWebpageResponseContextTest extends ContaoTestCase
{
    public function testResponseContext(): void
    {
        /** @var PageModel $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->title = 'My title';
        $pageModel->description = 'My description';
        $pageModel->robots = 'noindex,nofollow';

        $inner = new WebpageResponseContext($this->createMock(ResponseContextInterface::class), new HtmlHeadManager());
        $context = new ContaoWebpageResponseContext($inner, $pageModel);

        $this->assertSame('My title', $context->getHtmlHeadManager()->getTitle());
        $this->assertSame('My description', $context->getHtmlHeadManager()->getMetaDescription());
        $this->assertSame('noindex,nofollow', $context->getHtmlHeadManager()->getMetaRobots());
    }
}
