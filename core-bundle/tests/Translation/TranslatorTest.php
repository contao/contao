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

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\Translator;
use Contao\System;
use Symfony\Component\Translation\Translator as BaseTranslator;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslatorTest extends TestCase
{
    public function testTranslatorImplementsDeprecatedInterface(): void
    {
        $translator = new Translator($this->createMock(BaseTranslator::class), $this->mockContaoFramework());
        $this->assertInstanceOf(TranslatorInterface::class, $translator);
        $this->assertInstanceOf(LegacyTranslatorInterface::class, $translator);
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

        $translator = new Translator($originalTranslator, $framework);

        $this->assertSame('trans', $translator->trans('id', ['param' => 'value'], $domain, 'en'));

        $translator->setLocale('en');

        $this->assertSame('en', $translator->getLocale());
    }

    public function decoratedTranslatorDomainProvider(): \Generator
    {
        yield ['domain'];
        yield ['ContaoCoreBundle'];
    }

    /**
     * @group legacy
     *
     * @expectedDeprecation The Symfony\Component\Translation\Translator::transChoice method is deprecated %s.
     */
    public function testForwardsTheLegacyMethodCallsToTheDecoratedTranslator(): void
    {
        $originalTranslator = $this->createMock(BaseTranslator::class);
        $originalTranslator
            ->expects($this->once())
            ->method('transChoice')
            ->with('id', 3, ['param' => 'value'], 'domain', 'en')
            ->willReturn('transChoice')
        ;

        $translator = new Translator($originalTranslator, $this->mockContaoFramework());

        $this->assertSame('transChoice', $translator->transChoice('id', 3, ['param' => 'value'], 'domain', 'en'));
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

        $originalTranslator = $this->createMock(BaseTranslator::class);
        $originalTranslator
            ->method('getLocale')
            ->willReturn('en')
        ;

        $translator = new Translator($originalTranslator, $framework);

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
            ->expects($this->exactly(2))
            ->method('getLocale')
            ->willReturn('de')
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

        $translator = new Translator($originalTranslator, $framework);

        $this->assertSame('MSC.foo', $translator->trans('MSC.foo', [], 'contao_default'));
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

        $translator = new Translator($originalTranslator, $framework);
        $translator->setLocale('en');
        $translator->trans('foobar', [], 'contao_default', 'de');
    }
}
