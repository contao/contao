<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Translation;

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\MessageCatalogue;
use Contao\CoreBundle\Translation\Translator;
use Contao\System;
use Symfony\Bridge\PhpUnit\ExpectDeprecationTrait;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorTest extends TestCase
{
    use ExpectDeprecationTrait;

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANG']);

        parent::tearDown();
    }

    /**
     * @dataProvider decoratedTranslatorDomainProvider
     */
    public function testForwardsTheMethodCallsToTheDecoratedTranslator(string $domain): void
    {
        $originalTranslator = $this->createMock(BaseTranslator::class);
        $originalTranslator
            ->expects($this->once())
            ->method('trans')
            ->with('id', ['param' => 'value'], $domain, 'en')
            ->willReturn('trans')
        ;

        $originalTranslator
            ->expects($this->once())
            ->method('setLocale')
            ->with('en')
        ;

        $originalTranslator
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('en')
        ;

        $framework = $this->mockContaoFramework();
        $framework
            ->expects($this->never())
            ->method('initialize')
        ;

        $translator = $this->createTranslator($originalTranslator, $framework);

        $this->assertSame('trans', $translator->trans('id', ['param' => 'value'], $domain, 'en'));

        $translator->setLocale('en');

        $this->assertSame('en', $translator->getLocale());
    }

    public function decoratedTranslatorDomainProvider(): \Generator
    {
        yield ['domain'];
        yield ['ContaoCoreBundle'];
    }

    public function testReadsFromTheGlobalLanguageArray(): void
    {
        $adapter = $this->mockAdapter(['loadLanguageFile']);
        $adapter
            ->expects($this->atLeastOnce())
            ->method('loadLanguageFile')
            ->with('default')
        ;

        $framework = $this->mockContaoFramework([System::class => $adapter]);
        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $translator = $this->createTranslator(null, $framework);

        $this->assertSame('MSC.foo', $translator->trans('MSC.foo', [], 'contao_default'));

        $GLOBALS['TL_LANG']['MSC']['foo'] = 'bar';

        $this->assertSame('bar', $translator->trans('MSC.foo', [], 'contao_default'));
        $this->assertSame('MSC.foo.bar', $translator->trans('MSC.foo.bar', [], 'contao_default'));
        $this->assertSame('MSC.foo.0', $translator->trans('MSC.foo.0', [], 'contao_default'));
        $this->assertSame('MSC.foo.123', $translator->trans('MSC.foo.123', [], 'contao_default'));

        $GLOBALS['TL_LANG']['MSC']['foo'] = 'bar %s baz %s';

        $this->assertSame('bar foo1 baz foo2', $translator->trans('MSC.foo', ['foo1', 'foo2'], 'contao_default'));

        $GLOBALS['TL_LANG']['MSC']['foo.bar\baz'] = 'foo';

        $this->assertSame('foo', $translator->trans('MSC.foo\.bar\\\\baz', [], 'contao_default'));
        $this->assertSame('foo', $translator->trans('MSC.foo\.bar\baz', [], 'contao_default'));

        $GLOBALS['TL_LANG']['MSC']['foo\\']['bar\baz.'] = 'foo';

        $this->assertSame('foo', $translator->trans('MSC.foo\\\\.bar\baz\.', [], 'contao_default'));
        $this->assertSame('MSC.foo\.bar\baz\.', $translator->trans('MSC.foo\.bar\baz\.', [], 'contao_default'));

        unset(
            $GLOBALS['TL_LANG']['MSC']['foo'],
            $GLOBALS['TL_LANG']['MSC']['foo.bar\baz'],
            $GLOBALS['TL_LANG']['MSC']['foo\\']['bar\baz.']
        );
    }

    public function testUsesTheLocaleOfTheDecoratedTranslatorIfNoneIsGiven(): void
    {
        $originalTranslator = $this->createMock(BaseTranslator::class);
        $originalTranslator
            ->expects($this->atLeastOnce())
            ->method('getCatalogue')
            ->willReturnCallback(
                function ($locale) {
                    $catalogue = $this->createMock(MessageCatalogueInterface::class);
                    $catalogue
                        ->method('getLocale')
                        ->willReturn($locale ?? 'de')
                    ;

                    return $catalogue;
                }
            )
        ;

        $adapter = $this->mockAdapter(['loadLanguageFile']);
        $adapter
            ->expects($this->atLeastOnce())
            ->method('loadLanguageFile')
            ->with('default', 'de')
        ;

        $framework = $this->mockContaoFramework([System::class => $adapter]);
        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $translator = $this->createTranslator($originalTranslator, $framework);

        $this->assertSame('MSC.foo', $translator->trans('MSC.foo', [], 'contao_default'));
    }

    public function testUsesADecoratedCatalogue(): void
    {
        $originalCatalogue = $this->createMock(MessageCatalogueInterface::class);

        $originalTranslator = $this->createMock(BaseTranslator::class);
        $originalTranslator
            ->expects($this->atLeastOnce())
            ->method('getCatalogue')
            ->willReturn($originalCatalogue)
        ;

        $translator = $this->createTranslator($originalTranslator);

        $this->assertNotSame($originalCatalogue, $translator->getCatalogue());
    }

    public function testUsesDecoratedCatalogues(): void
    {
        $originalCatalogueDe = $this->createMock(MessageCatalogueInterface::class);
        $originalCatalogueDe
            ->method('getLocale')
            ->willReturn('de')
        ;

        $originalCatalogueEn = $this->createMock(MessageCatalogueInterface::class);
        $originalCatalogueEn
            ->method('getLocale')
            ->willReturn('en')
        ;

        $originalTranslator = $this->createMock(BaseTranslator::class);
        $originalTranslator
            ->method('getCatalogues')
            ->willReturn([$originalCatalogueDe, $originalCatalogueEn])
        ;

        $originalTranslator
            ->method('getCatalogue')
            ->willReturnMap([
                ['de', $originalCatalogueDe],
                ['en', $originalCatalogueEn],
            ])
        ;

        $translator = $this->createTranslator($originalTranslator);
        $catalogues = $translator->getCatalogues();

        $this->assertCount(2, $catalogues);
        $this->assertSame('de', $catalogues[0]->getLocale());
        $this->assertSame('en', $catalogues[1]->getLocale());
        $this->assertNotSame($originalCatalogueDe, $catalogues[0]);
        $this->assertNotSame($originalCatalogueEn, $catalogues[1]);
        $this->assertInstanceOf(MessageCatalogue::class, $catalogues[0]);
        $this->assertInstanceOf(MessageCatalogue::class, $catalogues[1]);
    }

    public function testRestoresPreviousTranslationsInGlobals(): void
    {
        $originalTranslator = $this->createMock(BaseTranslator::class);
        $originalTranslator
            ->expects($this->never())
            ->method('trans')
        ;

        $originalTranslator
            ->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturn('en')
        ;

        $originalTranslator
            ->method('getCatalogue')
            ->willReturnCallback(
                function ($locale) {
                    $catalogue = $this->createMock(MessageCatalogueInterface::class);
                    $catalogue
                        ->method('getLocale')
                        ->willReturn($locale ?? 'en')
                    ;

                    return $catalogue;
                }
            )
        ;

        $adapter = $this->mockAdapter(['loadLanguageFile']);
        $adapter
            ->expects($this->exactly(2))
            ->method('loadLanguageFile')
            ->withConsecutive(['default', 'de'], ['default', 'en'])
        ;

        $framework = $this->mockContaoFramework([System::class => $adapter]);
        $framework
            ->expects($this->atLeastOnce())
            ->method('initialize')
        ;

        $translator = $this->createTranslator($originalTranslator, $framework);
        $translator->setLocale('en');
        $translator->trans('foobar', [], 'contao_default', 'de');
    }

    private function createTranslator(TranslatorInterface|null $translator = null, ContaoFramework|null $framework = null, ResourceFinder|null $resourceFinder = null): Translator
    {
        if (!$translator) {
            $translator = $this->createMock(BaseTranslator::class);
            $translator
                ->method('getCatalogue')
                ->willReturnCallback(
                    function ($locale) {
                        $catalogue = $this->createMock(MessageCatalogueInterface::class);
                        $catalogue
                            ->method('getLocale')
                            ->willReturn($locale ?? 'de')
                        ;

                        return $catalogue;
                    }
                )
            ;
        }

        $framework ??= $this->mockContaoFramework();
        $resourceFinder ??= $this->createMock(ResourceFinder::class);

        return new Translator($translator, $framework, $resourceFinder);
    }
}
