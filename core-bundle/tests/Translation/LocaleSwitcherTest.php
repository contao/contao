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
use Contao\CoreBundle\Translation\LocaleSwitcher;
use Contao\CoreBundle\Translation\Translator;
use Contao\CoreBundle\Translation\TranslatorCallback;
use Contao\System;
use Symfony\Component\Translation\LocaleSwitcher as SymfonyLocaleSwitcher;

class LocaleSwitcherTest extends TestCase
{
    public function testLoadsLanguageFileForPreviousLanguage(): void
    {
        $translator = $this->createMock(Translator::class);
        $callback = new TranslatorCallback($translator, 'foo', [], 'contao_default');

        $inner = $this->createMock(SymfonyLocaleSwitcher::class);
        $inner
            ->expects($this->exactly(1))
            ->method('runWithLocale')
            ->with('de', $callback)
        ;

        $inner
            ->expects($this->exactly(1))
            ->method('getLocale')
            ->willReturn('en')
        ;

        $system = $this->mockAdapter(['loadLanguageFile']);
        $system
            ->expects($this->exactly(1))
            ->method('loadLanguageFile')
            ->with('default', 'en')
        ;

        $framework = $this->mockContaoFramework([System::class => $system]);

        $switcher = new LocaleSwitcher($inner, $framework);
        $switcher->runWithLocale('de', $callback);
    }

    public function testDoesNotLoadLanguageFileIfNotContaoDomain(): void
    {
        $translator = $this->createMock(Translator::class);
        $callback = new TranslatorCallback($translator, 'foo', [], 'messages');

        $inner = $this->createMock(SymfonyLocaleSwitcher::class);
        $inner
            ->expects($this->exactly(1))
            ->method('runWithLocale')
            ->with('de', $callback)
        ;

        $inner
            ->expects($this->exactly(1))
            ->method('getLocale')
            ->willReturn('en')
        ;

        $system = $this->mockAdapter(['loadLanguageFile']);
        $system
            ->expects($this->never())
            ->method('loadLanguageFile')
        ;

        $framework = $this->mockContaoFramework([System::class => $system]);

        $switcher = new LocaleSwitcher($inner, $framework);
        $switcher->runWithLocale('de', $callback);
    }

    public function testDoesNotLoadLanguageFileIfLanguageIsEqual(): void
    {
        $translator = $this->createMock(Translator::class);
        $callback = new TranslatorCallback($translator, 'foo', [], 'messages');

        $inner = $this->createMock(SymfonyLocaleSwitcher::class);
        $inner
            ->expects($this->exactly(1))
            ->method('runWithLocale')
            ->with('en', $callback)
        ;

        $inner
            ->expects($this->exactly(1))
            ->method('getLocale')
            ->willReturn('en')
        ;

        $system = $this->mockAdapter(['loadLanguageFile']);
        $system
            ->expects($this->never())
            ->method('loadLanguageFile')
        ;

        $framework = $this->mockContaoFramework([System::class => $system]);

        $switcher = new LocaleSwitcher($inner, $framework);
        $switcher->runWithLocale('en', $callback);
    }
}
