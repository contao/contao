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
        private MessageCatalogueInterface $parent,
        private ContaoFramework $framework,
        private ResourceFinder $resourceFinder,
    ) {
    }

    public function getLocale(): string
    {
        return $this->parent->getLocale();
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

        return [...$this->parent->getDomains(), ...$domains];
    }

    public function all(string|null $domain = null): array
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Getting Contao translations via %s() is not yet supported', __METHOD__));
        }

        return $this->parent->all($domain);
    }

    public function set(string $id, string $translation, string $domain = 'messages'): void
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Setting Contao translations via %s() is not yet supported', __METHOD__));
        }

        $this->parent->set($id, $translation, $domain);
    }

    public function has(string $id, string $domain = 'messages'): bool
    {
        if (!$this->isContaoDomain($domain)) {
            return $this->parent->has($id, $domain);
        }

        return null !== $this->loadMessage($id, $domain);
    }

    public function defines(string $id, string $domain = 'messages'): bool
    {
        if (!$this->isContaoDomain($domain)) {
            return $this->parent->defines($id, $domain);
        }

        return null !== $this->loadMessage($id, $domain);
    }

    public function get(string $id, string $domain = 'messages'): string
    {
        if (!$this->isContaoDomain($domain)) {
            return $this->parent->get($id, $domain);
        }

        return $this->loadMessage($id, $domain) ?? $id;
    }

    public function replace(array $messages, string $domain = 'messages'): void
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Setting Contao translations via %s() is not yet supported', __METHOD__));
        }

        $this->parent->replace($messages, $domain);
    }

    public function add(array $messages, string $domain = 'messages'): void
    {
        if ($this->isContaoDomain($domain)) {
            throw new LogicException(sprintf('Setting Contao translations via %s() is not yet supported', __METHOD__));
        }

        $this->parent->add($messages, $domain);
    }

    public function addCatalogue(MessageCatalogueInterface $catalogue): void
    {
        $this->parent->addCatalogue($catalogue);
    }

    public function addFallbackCatalogue(MessageCatalogueInterface $catalogue): void
    {
        $this->parent->addFallbackCatalogue($catalogue);
    }

    public function getFallbackCatalogue(): MessageCatalogueInterface|null
    {
        return $this->parent->getFallbackCatalogue();
    }

    public function getResources(): array
    {
        return $this->parent->getResources();
    }

    public function addResource(ResourceInterface $resource): void
    {
        $this->parent->addResource($resource);
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

        return $this->getFromGlobals($id);
    }

    /**
     * Returns the labels from $GLOBALS['TL_LANG'] based on a message ID like "MSC.view".
     */
    private function getFromGlobals(string $id): string|null
    {
        // Split the ID into chunks allowing escaped dots (\.) and backslashes (\\)
        preg_match_all('/(?:\\\\[\\\\.]|[^.])++/', $id, $matches);

        /** @var array<string> $parts */
        $parts = preg_replace('/\\\\([\\\\.])/', '$1', $matches[0]);
        $item = &$GLOBALS['TL_LANG'];

        foreach ($parts as $part) {
            if (!\is_array($item) || !isset($item[$part])) {
                return null;
            }

            $item = &$item[$part];
        }

        if (\is_array($item)) {
            return null;
        }

        return (string) $item;
    }
}
