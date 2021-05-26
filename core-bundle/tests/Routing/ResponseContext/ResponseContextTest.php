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

use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\ResponseContext;
use PHPUnit\Framework\TestCase;

class ResponseContextTest extends TestCase
{
    public function testAddingAndGettingServices(): void
    {
        $context = new ResponseContext();

        $this->assertFalse($context->has(HtmlHeadBag::class));
        $this->assertNull($context->get(HtmlHeadBag::class));

        $context->add(new HtmlHeadBag());

        $this->assertTrue($context->has(HtmlHeadBag::class));
        $this->assertInstanceOf(HtmlHeadBag::class, $context->get(HtmlHeadBag::class));
    }

    public function testHeaderBagIsInitializedCompletelyEmpty(): void
    {
        $context = new ResponseContext();

        $this->assertCount(0, $context->getHeaderBag()->all());
    }
}
