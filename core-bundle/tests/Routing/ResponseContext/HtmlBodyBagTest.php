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

use Contao\CoreBundle\Routing\ResponseContext\HtmlBodyBag;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use PHPUnit\Framework\TestCase;

class HtmlBodyBagTest extends TestCase
{
    public function testCollectsStructuredAndRawContent(): void
    {
        $bag = new HtmlBodyBag();
        $bag
            ->add(HtmlTag::script('/app.js'))
            ->add('<script>legacy()</script>')
        ;

        $this->assertSame($bag->all(), iterator_to_array($bag));
        $this->assertSame('/app.js', $bag->all()[0]->getAttributes()['src']);
        $this->assertSame('<script>legacy()</script>', $bag->all()[1]);
    }
}
