<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\EventListener\DataContainer\LegacyRoutingListener;
use Contao\CoreBundle\Framework\ContaoFramework;
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

    public function testDoesNothingInNonLegacyMode(): void
    {
        $listener = new LegacyRoutingListener(
            $this->mockContaoFrameworkWithLegacyRouting(false),
            $this->createMock(TranslatorInterface::class)
        );

        $GLOBALS['TL_DCA']['tl_page']['palettes'] = ['root' => '', 'rootfallback' => ''];
        $GLOBALS['TL_DCA']['tl_page']['fields'] = [
            'languagePrefix' => [],
            'urlSuffix' => [],
        ];

        $listener->disableRoutingFields();

        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']['fields']['languagePrefix']);
        $this->assertEmpty($GLOBALS['TL_DCA']['tl_page']['fields']['urlSuffix']);
    }

    public function testDisabledThePageFieldsInLegacyMode(): void
    {
        $listener = new LegacyRoutingListener(
            $this->mockContaoFrameworkWithLegacyRouting(true),
            $this->createMock(TranslatorInterface::class)
        );

        $GLOBALS['TL_DCA']['tl_page']['palettes'] = ['root' => '', 'rootfallback' => ''];
        $GLOBALS['TL_DCA']['tl_page']['fields'] = [
            'languagePrefix' => [],
            'urlSuffix' => [],
        ];

        $listener->disableRoutingFields();

        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['fields']['languagePrefix']['eval']['disabled']);
        $this->assertTrue($GLOBALS['TL_DCA']['tl_page']['fields']['urlSuffix']['eval']['disabled']);
    }

    public function testAddsTheRoutingWarningInLegacyMode(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('tl_page.legacyRouting', [], 'contao_tl_page')
            ->willReturn('warning')
        ;

        $listener = new LegacyRoutingListener(
            $this->mockContaoFrameworkWithLegacyRouting(true),
            $translator
        );

        $GLOBALS['TL_DCA']['tl_page']['palettes'] = ['root' => '', 'rootfallback' => ''];
        $GLOBALS['TL_DCA']['tl_page']['fields'] = [
            'languagePrefix' => [],
            'urlSuffix' => [],
        ];

        $listener->disableRoutingFields();

        $this->assertIsCallable($GLOBALS['TL_DCA']['tl_page']['fields']['legacy_routing']['input_field_callback']);
        $this->assertSame(
            '<p class="tl_gerror">warning</p>',
            \call_user_func($GLOBALS['TL_DCA']['tl_page']['fields']['legacy_routing']['input_field_callback'])
        );
    }

    public function testOverridesTheLanguagePrefixWithPrependLocale(): void
    {
        $listener = new LegacyRoutingListener(
            $this->mockContaoFrameworkWithLegacyRouting(true),
            $this->createMock(TranslatorInterface::class),
            true
        );

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class, ['activeRecord' => (object) ['language' => 'en-US']]);

        $this->assertSame('en-US', $listener->overrideLanguagePrefix('foo', $dc));
    }

    public function testOverridesTheLanguagePrefixWithoutPrependLocale(): void
    {
        $listener = new LegacyRoutingListener(
            $this->mockContaoFrameworkWithLegacyRouting(true),
            $this->createMock(TranslatorInterface::class),
            false
        );

        /** @var DataContainer&MockObject $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class, ['activeRecord' => (object) ['language' => 'en-US']]);

        $this->assertSame('', $listener->overrideLanguagePrefix('foo', $dc));
    }

    public function testOverridesTheUrlSuffix(): void
    {
        $listener = new LegacyRoutingListener(
            $this->mockContaoFrameworkWithLegacyRouting(true),
            $this->createMock(TranslatorInterface::class),
            false,
            '.bar'
        );

        $this->assertSame('.bar', $listener->overrideUrlSuffix('foo'));
    }

    public function testReturnsTheOriginalFieldValuesWithoutLegacyMode(): void
    {
        $listener = new LegacyRoutingListener(
            $this->mockContaoFrameworkWithLegacyRouting(false),
            $this->createMock(TranslatorInterface::class)
        );

        $dc = $this->createMock(DataContainer::class);
        $dc->activeRecord = (object) ['language' => 'en-US'];

        $this->assertSame('foo', $listener->overrideLanguagePrefix('foo', $dc));
        $this->assertSame('foo', $listener->overrideUrlSuffix('foo'));
    }

    private function mockContaoFrameworkWithLegacyRouting(bool $legacyRouting)
    {
        $framework = $this->createMock(ContaoFramework::class);

        $framework
            ->expects($this->atLeastOnce())
            ->method('isLegacyRouting')
            ->willReturn($legacyRouting)
        ;

        return $framework;
    }
}
