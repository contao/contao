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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Translation\LegacyLocaleSwitcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class LegacyLocaleSwitcherTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_LANGUAGE']);

        parent::tearDown();
    }

    public function testSetsTheSuperGlobal(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->exactly(2))
            ->method('isInitialized')
            ->willReturn(true)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->never())
            ->method('getLocale')
        ;

        $legacyLocaleSwitcher = new LegacyLocaleSwitcher($framework, $this->createStub(TranslatorInterface::class));
        $legacyLocaleSwitcher->setLocale('de_AT');

        $this->assertSame('de-AT', $GLOBALS['TL_LANGUAGE']);
        $this->assertSame('de_AT', $legacyLocaleSwitcher->getLocale());
    }

    public function testDoesNotSetTheSuperGlobalIfTheFrameworkIsNotInitialized(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework
            ->expects($this->exactly(2))
            ->method('isInitialized')
            ->willReturn(false)
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('en')
        ;

        $legacyLocaleSwitcher = new LegacyLocaleSwitcher($framework, $translator);
        $legacyLocaleSwitcher->setLocale('de_AT');

        $this->assertFalse(isset($GLOBALS['TL_LANGUAGE']));
        $this->assertSame('en', $legacyLocaleSwitcher->getLocale());
    }
}
