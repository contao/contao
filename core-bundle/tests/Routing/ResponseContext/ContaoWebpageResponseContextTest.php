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
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class ContaoWebpageResponseContextTest extends ContaoTestCase
{
    public function testResponseContext(): void
    {
        /** @var PageModel&MockObject $pageModel */
        $pageModel = $this->mockClassWithProperties(PageModel::class);
        $pageModel->title = 'My title';
        $pageModel->description = 'My description';

        $context = new ContaoWebpageResponseContext($pageModel);

        $this->assertSame('My title', $context->getTitle());
        $this->assertSame('My description', $context->getMetaDescription());
    }
}
