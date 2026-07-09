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
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DataCollectorTranslatorTest extends TestCase
{
    public function testCollectsMessages(): void
    {
        $originalTranslator = $this->createTranslator('Main navigation', 'en');

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
            $translator->getCollectedMessages(),
        );
    }

    public function testDoesNotCollectMessagesIfTheDomainIsNotAContaoDomain(): void
    {
        $originalTranslator = $this->createTranslator('translation', 'en');

        $translator = new DataCollectorTranslator($originalTranslator);

        $this->assertSame('translation', $translator->trans('translation_key', [], 'message_domain', 'en'));
        $this->assertSame([], $translator->getCollectedMessages());
    }

    public function testServiceIsResetable(): void
    {
        $originalTranslator = $this->createTranslator('bar', 'en');

        $translator = new DataCollectorTranslator($originalTranslator);

        $this->assertEmpty($translator->getCollectedMessages());

        $translator->trans('foo', [], 'contao_default');

        $this->assertCount(1, $translator->getCollectedMessages());

        $translator->reset();

        $this->assertEmpty($translator->getCollectedMessages());
    }

    private function createTranslator(string $translation, string $locale): TranslatorInterface&TranslatorBagInterface&LocaleAwareInterface
    {
        return new class($translation, $locale) implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface {
            private array $collectedMessages = [];

            public function __construct(
                private readonly string $translation,
                private string $locale,
            ) {
            }

            public function trans(string|null $id, array $parameters = [], string|null $domain = null, string|null $locale = null): string
            {
                return $this->translation;
            }

            public function getCatalogue(string|null $locale = null): MessageCatalogueInterface
            {
                return new MessageCatalogue($locale ?? $this->locale);
            }

            public function getCatalogues(): array
            {
                return [new MessageCatalogue($this->locale)];
            }

            public function setLocale(string $locale): void
            {
                $this->locale = $locale;
            }

            public function getLocale(): string
            {
                return $this->locale;
            }

            public function getCollectedMessages(): array
            {
                return $this->collectedMessages;
            }
        };
    }
}
