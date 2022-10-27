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

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface, LegacyTranslatorInterface
{
    /**
     * @var TranslatorInterface|TranslatorBagInterface|LegacyTranslatorInterface
     */
    private $translator;

    /**
     * @var ContaoFramework
     */
    private $framework;

    /**
     * @internal Do not inherit from this class; decorate the "contao.translation.translator" service instead
     */
    public function __construct(TranslatorInterface $translator, ContaoFramework $framework)
    {
        $this->translator = $translator;
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     *
     * Gets the translation from Contao’s $GLOBALS['TL_LANG'] array if the message
     * domain starts with "contao_". The locale parameter is ignored in this case.
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        // Forward to the default translator
        if (null === $domain || 0 !== strncmp($domain, 'contao_', 7)) {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        if (null === $locale) {
            $locale = $this->translator->getLocale();
        }

        $contaoDomain = substr($domain, 7);

        $this->framework->initialize();
        $this->loadLanguageFile($contaoDomain, $locale);

        $translated = $this->getFromGlobals($id);

        if (!empty($parameters) && null !== $translated) {
            $translated = vsprintf($translated, $parameters);
        }

        // Restore previous translations in $GLOBALS['TL_LANG'] (#5371)
        if ($locale !== $this->getLocale()) {
            $this->loadLanguageFile($contaoDomain, $this->getLocale());
        }

        return $translated ?? $id;
    }

    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null): string
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    public function setLocale($locale): void
    {
        $this->translator->setLocale($locale);
    }

    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    public function getCatalogue($locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Returns the collected messages of the decorated translator.
     */
    public function getCollectedMessages(): array
    {
        if (method_exists($this->translator, 'getCollectedMessages')) {
            return $this->translator->getCollectedMessages();
        }

        return [];
    }

    /**
     * Returns the fallback locales of the decorated translator.
     */
    public function getFallbackLocales(): array
    {
        if (method_exists($this->translator, 'getFallbackLocales')) {
            return $this->translator->getFallbackLocales();
        }

        return [];
    }

    /**
     * Returns the labels from $GLOBALS['TL_LANG'] based on a message ID like "MSC.view".
     */
    private function getFromGlobals(string $id): ?string
    {
        // Split the ID into chunks allowing escaped dots (\.) and backslashes (\\)
        preg_match_all('/(?:\\\\[\\\\.]|[^.])++/', $id, $matches);
        $parts = preg_replace('/\\\\([\\\\.])/', '$1', $matches[0]);

        $item = &$GLOBALS['TL_LANG'];

        foreach ($parts as $part) {
            if (!\is_array($item) || !isset($item[$part])) {
                return null;
            }

            $item = &$item[$part];
        }

        return $item;
    }

    /**
     * Loads a Contao framework language file.
     */
    private function loadLanguageFile(string $name, string $locale = null): void
    {
        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);
        $system->loadLanguageFile($name, $locale);
    }
}
