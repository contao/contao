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

use Contao\CoreBundle\Config\ResourceFinder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Symfony\Component\Translation\MessageCatalogueInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Translator implements TranslatorInterface, TranslatorBagInterface, LocaleAwareInterface
{
    /**
     * @var \SplObjectStorage<MessageCatalogueInterface, MessageCatalogue>
     */
    private readonly \SplObjectStorage $catalogues;

    /**
     * @internal
     */
    public function __construct(
        private readonly LocaleAwareInterface|TranslatorBagInterface|TranslatorInterface $translator,
        private readonly ContaoFramework $framework,
        private readonly ResourceFinder $resourceFinder,
    ) {
        $this->catalogues = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     *
     * Gets the translation from Contao’s $GLOBALS['TL_LANG'] array if the message
     * domain starts with "contao_".
     */
    public function trans(string $id, array $parameters = [], string|null $domain = null, string|null $locale = null): string
    {
        // Forward to the default translator
        if (null === $domain || !str_starts_with($domain, 'contao_')) {
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        $translated = $this->getCatalogue($locale)->get($id, $domain);

        if ($parameters) {
            $translated = vsprintf($translated, $parameters);
        }

        // Restore previous translations in $GLOBALS['TL_LANG'] (see #5371)
        if (null !== $locale && $locale !== $this->getLocale()) {
            $system = $this->framework->getAdapter(System::class);
            $system->loadLanguageFile(substr($domain, 7), $this->getLocale());
        }

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
        $parentCatalog = $this->translator->getCatalogue($locale);

        if (!$this->catalogues->contains($parentCatalog)) {
            $this->catalogues->attach(
                $parentCatalog,
                new MessageCatalogue($parentCatalog, $this->framework, $this->resourceFinder)
            );
        }

        return $this->catalogues->offsetGet($parentCatalog);
    }

    public function getCatalogues(): array
    {
        if (!method_exists($this->translator, 'getCatalogues')) {
            return [];
        }

        $catalogues = [];

        foreach ($this->translator->getCatalogues() as $catalogue) {
            $catalogues[] = $this->getCatalogue($catalogue->getLocale());
        }

        return $catalogues;
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
}
