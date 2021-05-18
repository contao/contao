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

use Contao\CoreBundle\Routing\ResponseContext\CoreResponseContextFactory;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContextAccessor;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;

class CoreResponseFactoryTest extends ContaoTestCase
{
    public function testFactoryMethods(): void
    {
        $responseAccessor = $this->createMock(ResponseContextAccessor::class);
        $responseAccessor
            ->expects($this->exactly(3))
            ->method('setResponseContext')
        ;

        $factory = new CoreResponseContextFactory($responseAccessor);
        $factory->createResponseContext();
        $factory->createWebpageResponseContext();
        $factory->createContaoWebpageResponseContext($this->createMock(PageModel::class));
    }
}
