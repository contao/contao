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
use Contao\CoreBundle\Translation\DataCollectorTranslator;
use Symfony\Component\Translation\DataCollectorTranslator as SymfonyDataCollectorTranslator;

class DataCollectorTranslatorTest extends TestCase
{
    public function testCollectsMessages(): void
    {
        $originalTranslator = $this->createMock(SymfonyDataCollectorTranslator::class);
        $originalTranslator
            ->expects($this->once())
            ->method('trans')
            ->with('MSC.mainNavigation', [], 'contao_default', 'en')
            ->willReturn('Main navigation')
        ;

        $originalTranslator
            ->expects($this->once())
            ->method('getLocale')
            ->willReturn('en')
        ;

        $originalTranslator
            ->expects($this->once())
            ->method('getCollectedMessages')
            ->willReturn([])
        ;

        $translator = new DataCollectorTranslator($originalTranslator);
        $translator->trans('MSC.mainNavigation', [], 'contao_default', 'en');

        $this->assertSame(
            [[
                'locale' => 'en',
                'domain' => 'contao_default',
                'id' => 'MSC.mainNavigation',
                'translation' => 'Main navigation',
                'parameters' => [],
                'state' => 0,
                'transChoiceNumber' => null,
            ]],
            $translator->getCollectedMessages()
        );
    }

    public function testDoesNotCollectMessagesIfTheDomainIsNotAContaoDomain(): void
    {
        $originalTranslator = $this->createMock(SymfonyDataCollectorTranslator::class);
        $originalTranslator
            ->expects($this->once())
            ->method('trans')
            ->with('translation_key', [], 'message_domain', 'en')
            ->willReturn('translation')
        ;

        $originalTranslator
            ->expects($this->never())
            ->method('getCollectedMessages')
        ;

        $translator = new DataCollectorTranslator($originalTranslator);

        $this->assertSame('translation', $translator->trans('translation_key', [], 'message_domain', 'en'));
    }
}
