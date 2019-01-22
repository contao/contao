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
use Symfony\Component\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface
{
    /**
     * @var TranslatorInterface|TranslatorBagInterface
     */
    private $translator;

    /**
     * @var ContaoFramework
     */
    private $framework;

    public function __construct(TranslatorInterface $translator, ContaoFramework $framework)
    {
        $this->translator = $translator;
        $this->framework = $framework;
    }

    /**
     * {@inheritdoc}
     *
     * Gets the translation from Contao’s $GLOBALS['TL_LANG'] array if the message domain starts with
     * "contao_". The locale parameter is ignored in this case.
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null): string
    {
        // Forward to the default translator
        if (null === $domain || 0 !== strncmp($domain, 'contao_', 7)) {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        $this->framework->initialize();
        $this->loadLanguageFile(substr($domain, 7));

        $translated = $this->getFromGlobals($id);

        if (null === $translated) {
            return $id;
        }

        if (!empty($parameters)) {
            $translated = vsprintf($translated, $parameters);
        }

        return $translated;
    }

    /**
     * {@inheritdoc}
     */
    public function transChoice($id, $number, array $parameters = [], $domain = null, $locale = null): string
    {
        return $this->translator->transChoice($id, $number, $parameters, $domain, $locale);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocale($locale): ?string
    {
        return $this->translator->setLocale($locale);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocale(): string
    {
        return $this->translator->getLocale();
    }

    /**
     * {@inheritdoc}
     */
    public function getCatalogue($locale = null): MessageCatalogueInterface
    {
        return $this->translator->getCatalogue($locale);
    }

    /**
     * Returns the labels from $GLOBALS['TL_LANG'] based on a message ID like "MSC.view".
     */
    private function getFromGlobals(string $id): ?string
    {
        // Split the ID into chunks allowing escaped dots (\.) and backslashes (\\)
        preg_match_all('/(?:\\\\[\.\\\\]|[^\.])++/', $id, $matches);
        $parts = preg_replace('/\\\\([\.\\\\])/', '$1', $matches[0]);

        $item = &$GLOBALS['TL_LANG'];

        foreach ($parts as $part) {
            if (!isset($item[$part])) {
                return null;
            }

            $item = &$item[$part];
        }

        return $item;
    }

    /**
     * Loads a Contao framework language file.
     */
    private function loadLanguageFile(string $name): void
    {
        /** @var System $system */
        $system = $this->framework->getAdapter(System::class);
        $system->loadLanguageFile($name);
    }
}
