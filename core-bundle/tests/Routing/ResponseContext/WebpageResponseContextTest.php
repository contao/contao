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

use Contao\CoreBundle\Routing\ResponseContext\WebpageResponseContext;
use PHPUnit\Framework\TestCase;

class WebpageResponseContextTest extends TestCase
{
    public function testResponseContext(): void
    {
        $context = new WebpageResponseContext();
        $context->setTitle('foobar title');
        $context->setMetaDescription('foobar description');

        $this->assertSame('index,follow', $context->getMetaRobots()); // Test default

        $context->setMetaRobots('noindex,nofollow');

        $this->assertSame('foobar title', $context->getTitle());
        $this->assertSame('foobar description', $context->getMetaDescription());
        $this->assertSame('noindex,nofollow', $context->getMetaRobots());
    }
}
