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
use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Translation\Exception\LogicException;
use Symfony\Component\Translation\MessageCatalogueInterface;

final class MessageCatalogue implements MessageCatalogueInterface
{
    /**
     * @internal Do not instantiate this class; use Translator::getCatalogue() instead
     */
    public function __construct(
        private readonly MessageCatalogueInterface $inner,
        private readonly ContaoFramework $framework,
        private readonly ResourceFinder $resourceFinder,
    ) {
    }

    public function getLocale(): string
    {
        return $this->inner->getLocale();
    }

    public function getDomains(): array
    {
        $finder = $this->resourceFinder->findIn('languages/'.$this->getLocale())->name('/\.(php|xlf)$/');
        $domains = [];

        foreach ($finder as $file) {
            $domains['contao_'.$file->getBasename('.'.$file->getExtension())] = true;
        }

        $domains = array_keys($domains);
        sort($domains);

        return [...$this->inner->getDomains(), ...$domains];
    }

    public function all(string|null $domain = null): array
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Getting Contao translations via %s() is not yet supported', __METHOD__));
        }

        return $this->inner->all($domain);
    }

    public function set(string $id, string $translation, string $domain = 'messages'): void
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Setting Contao translations via %s() is not yet supported', __METHOD__));
        }

        $this->inner->set($id, $translation, $domain);
    }

    public function has(string $id, string $domain = 'messages'): bool
    {
        if (!$this->isContaoDomain($domain)) {
            return $this->inner->has($id, $domain);
        }

        return null !== $this->loadMessage($id, $domain);
    }

    public function defines(string $id, string $domain = 'messages'): bool
    {
        if (!$this->isContaoDomain($domain)) {
            return $this->inner->defines($id, $domain);
        }

        return null !== $this->loadMessage($id, $domain);
    }

    public function get(string $id, string $domain = 'messages'): string
    {
        if (!$this->isContaoDomain($domain)) {
            return $this->inner->get($id, $domain);
        }

        return $this->loadMessage($id, $domain) ?? $id;
    }

    public function replace(array $messages, string $domain = 'messages'): void
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Setting Contao translations via %s() is not yet supported', __METHOD__));
        }

        $this->inner->replace($messages, $domain);
    }

    public function add(array $messages, string $domain = 'messages'): void
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Setting Contao translations via %s() is not yet supported', __METHOD__));
        }

        $this->inner->add($messages, $domain);
    }

    public function addCatalogue(MessageCatalogueInterface $catalogue): void
    {
        $this->inner->addCatalogue($catalogue);
    }

    public function addFallbackCatalogue(MessageCatalogueInterface $catalogue): void
    {
        $this->inner->addFallbackCatalogue($catalogue);
    }

    public function getFallbackCatalogue(): MessageCatalogueInterface|null
    {
        return $this->inner->getFallbackCatalogue();
    }

    public function getResources(): array
    {
        return $this->inner->getResources();
    }

    public function addResource(ResourceInterface $resource): void
    {
        $this->inner->addResource($resource);
    }

    /**
     * Populates $GLOBALS['TL_LANG'] with all translations for the given domain
     * and also returns the PHP string representation.
     */
    public function populateGlobalsFromSymfony(string $domain): string
    {
        if (!$this->isContaoDomain($domain)) {
            return '';
        }

        $translations = $this->inner->all($domain);
        $return = '';

        foreach ($translations as $k => $v) {
            $parts = LegacyGlobalsProcessor::getPartsFromKey($k);

            $return .= LegacyGlobalsProcessor::getStringRepresentation($parts, $v);

            LegacyGlobalsProcessor::addGlobal($parts, $v);
        }

        return $return;
    }

    private function isContaoDomain(string|null $domain): bool
    {
        return str_starts_with($domain ?? '', 'contao_');
    }

    private function loadMessage(string $id, string $domain): string|null
    {
        $this->framework->initialize();

        $system = $this->framework->getAdapter(System::class);
        $system->loadLanguageFile(substr($domain, 7), $this->getLocale());

        return LegacyGlobalsProcessor::getFromGlobalsByKey($id);
    }
}
