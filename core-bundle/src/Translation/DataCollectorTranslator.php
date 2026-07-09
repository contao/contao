<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Translation;

use Symfony\Component\HttpKernel\CacheWarmer\WarmableInterface;
use Symfony\Component\Translation\DataCollectorTranslator as SymfonyDataCollectorTranslator;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class DataCollectorTranslator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface, WarmableInterface, ResetInterface
{
    private array $messages = [];

    public function __construct(private readonly TranslatorInterface&TranslatorBagInterface&LocaleAwareInterface $translator)
    {
    }

    public function __call(string $method, array $arguments): mixed
    {
        return $this->translator->{$method}(...$arguments);
    }

    /**
     * {@inheritdoc}
     *
     * Gets the translation from Contao’s $GLOBALS['TL_LANG'] array if the message
     * domain starts with "contao_". The locale parameter is ignored in this case.
     */
    public function trans(string|null $id, array $parameters = [], string|null $domain = null, string|null $locale = null): string
    {
        $translated = $this->translator->trans($id, $parameters, $domain, $locale);

        if (null === $domain || !str_starts_with($domain, 'contao_')) {
            return $translated;
        }

        $this->collectMessage($this->getLocale(), $domain, $id, $translated, $parameters);

        return $translated;
    }

    public function setLocale(string $locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function getCatalogue(string|null $locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    public function getCatalogues(): array
    {
        if (method_exists($this->translator, 'getCatalogues')) {
            return $this->translator->getCatalogues();
        }

        return [];
    }

    public function getFallbackLocales(): array
    {
        if (method_exists($this->translator, 'getFallbackLocales')) {
            return $this->translator->getFallbackLocales();
        }

        return [];
    }

    public function getGlobalParameters(): array
    {
        if (method_exists($this->translator, 'getGlobalParameters')) {
            return $this->translator->getGlobalParameters();
        }

        return [];
    }

    /**
     * Merges the collected messages from the decorated translator.
     */
    public function getCollectedMessages(): array
    {
        if (method_exists($this->translator, 'getCollectedMessages')) {
            return [...$this->translator->getCollectedMessages(), ...$this->messages];
        }

        return $this->messages;
    }

    public function reset(): void
    {
        $this->messages = [];
    }

    public function warmUp(string $cacheDir, string|null $buildDir = null): array
    {
        if ($this->translator instanceof WarmableInterface) {
            return $this->translator->warmUp($cacheDir, $buildDir);
        }

        return [];
    }

    private function collectMessage(string $locale, string $domain, string $id, string $translation, array $parameters = []): void
    {
        if ($id === $translation) {
            $state = SymfonyDataCollectorTranslator::MESSAGE_MISSING;
        } else {
            $state = SymfonyDataCollectorTranslator::MESSAGE_DEFINED;
        }

        $this->messages[] = [
            'locale' => $locale,
            'domain' => $domain,
            'id' => $id,
            'translation' => $translation,
            'parameters' => $parameters,
            'state' => $state,
            'transChoiceNumber' => isset($parameters['%count%']) && is_numeric($parameters['%count%']) ? $parameters['%count%'] : null,
        ];
    }
}
