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

use Contao\CoreBundle\EventListener\DataContainer\LegacyRoutingListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class LegacyRoutingListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TL_HOOKS'] = [];
        $GLOBALS['TL_DCA'] = [];
    }

    public function testDisabledThePageFields(): void
    {
        $listener = new LegacyRoutingListener($this->createMock(TranslatorInterface::class));

        $GLOBALS['TL_DCA']['tl_page']['palettes'] = ['root' => '', 'rootfallback' => ''];
        $GLOBALS['TL_DCA']['tl_page']['fields'] = [
            'urlPrefix' => [],
            'urlSuffix' => [],
        ];

        $listener->disableRoutingFields();

        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['fields']['urlPrefix']['eval']['disabled']);
        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['fields']['urlSuffix']['eval']['disabled']);
    }

    public function testAddsTheRoutingWarning(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('tl_page.legacyRouting', [], 'contao_tl_page')
            ->willReturn('warning')
        ;

        $listener = new LegacyRoutingListener($translator);

        $GLOBALS['TL_DCA']['tl_page']['palettes'] = ['root' => '', 'rootfallback' => ''];
        $GLOBALS['TL_DCA']['tl_page']['fields'] = [
            'urlPrefix' => [],
            'urlSuffix' => [],
        ];

        $listener->disableRoutingFields();

        $this->assertIsCallable($GLOBALS['TL_DCA']['tl_page']['fields']['legacy_routing']['input_field_callback']);
        $this->assertSame(
            '<p class="tl_gerror">warning</p>',
            \call_user_func($GLOBALS['TL_DCA']['tl_page']['fields']['legacy_routing']['input_field_callback'])
        );
    }

    public function testOverridesTheUrlPrefixWithPrependLocale(): void
    {
        $listener = new LegacyRoutingListener($this->createMock(TranslatorInterface::class), true);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(
            DataContainer::class,
            ['activeRecord' => (object)['language' => 'en-US']]
        );

        $this->assertSame('en-US', $listener->overrideUrlPrefix('foo', $dc));
    }

    public function testOverridesTheUrlPrefixWithoutPrependLocale(): void
    {
        $listener = new LegacyRoutingListener($this->createMock(TranslatorInterface::class), false);

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class, ['activeRecord' => (object) ['language' => 'en-US']]);

        $this->assertSame('', $listener->overrideUrlPrefix('foo', $dc));
    }

    public function testOverridesTheUrlSuffix(): void
    {
        $listener = new LegacyRoutingListener(
            $this->createMock(TranslatorInterface::class),
            false,
            '.bar'
        );

        $this->assertSame('.bar', $listener->overrideUrlSuffix());
    }
}
