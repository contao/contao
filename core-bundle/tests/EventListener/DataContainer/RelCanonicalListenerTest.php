<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\RelCanonicalListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image;
use Symfony\Contracts\Translation\TranslatorInterface;

class RelCanonicalListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    public function testAddsTheHelpWizard(): void
    {
        $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['eval'] = [];
        $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalKeepParams']['eval'] = [];

        $adapter = $this->mockAdapter(['getHtml']);
        $adapter
            ->expects($this->exactly(2))
            ->method('getHtml')
            ->with('show.svg', '', 'title="disabled"')
            ->willReturn('<img src="show.svg" alt="" title="disabled">')
        ;

        $framework = $this->mockContaoFramework([Image::class => $adapter]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->with('tl_page.relCanonical', [], 'contao_tl_page')
            ->willReturn('disabled')
        ;

        $listener = new RelCanonicalListener($framework, $translator);
        $listener->disableRoutingFields();

        $this->assertInstanceOf(\Closure::class, $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['xlabel'][0]);
        $this->assertInstanceOf(\Closure::class, $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalKeepParams']['xlabel'][0]);

        $this->assertSame('<img src="show.svg" alt="" title="disabled">', $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalLink']['xlabel'][0]());
        $this->assertSame('<img src="show.svg" alt="" title="disabled">', $GLOBALS['TL_DCA']['tl_page']['fields']['canonicalKeepParams']['xlabel'][0]());
    }
}
