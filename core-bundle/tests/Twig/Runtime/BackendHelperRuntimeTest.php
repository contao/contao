<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\BackendHelperRuntime;
use Contao\Image;

class BackendHelperRuntimeTest extends TestCase
{
    public function testDelegatesCalls(): void
    {
        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->with('icon.svg', 'alt', 'foo="bar"')
            ->willReturn('icon HTML')
        ;

        $framework = $this->mockContaoFramework([Image::class => $imageAdapter]);

        $this->assertSame('icon HTML', (new BackendHelperRuntime($framework))->icon(
            'icon.svg', 'alt', (new HtmlAttributes())->set('foo', 'bar'),
        ));
    }
}
