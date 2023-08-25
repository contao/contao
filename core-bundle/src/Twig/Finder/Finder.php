<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Finder;

use Contao\CoreBundle\Twig\ContaoTwigUtil;
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Loader\ThemeNamespace;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @experimental
 *
 * @implements \IteratorAggregate<string, string>
 */
final class Finder implements \IteratorAggregate, \Countable
{
    private string|null $identifier = null;
    private string|null $themeSlug = null;
    private string|null $extension = null;
    private bool $variantsExclusive = false;
    private bool $variants = false;

    /**
     * @var array<string, list<string>>
     */
    private array $sources = [];

    /**
     * @internal
     */
    public function __construct(
        private readonly TemplateHierarchyInterface $hierarchy,
        private readonly ThemeNamespace $themeNamespace,
        private readonly TranslatorBagInterface|TranslatorInterface $translator,
    ) {
    }

    /**
     * Filters templates based on the identifier, e.g. "content_element/text".
     */
    public function identifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * Filters templates based on the file extension, e.g. "html.twig" or "json.twig".
     */
    public function extension(string $extension): self
    {
        $this->extension = $extension;

        return $this;
    }

    /**
     * Filters templates based on the logical name or short name, e.g.
     * "@Contao/content_element/text.html.twig" or "content_element/text.html.twig".
     */
    public function name(string $name): self
    {
        $this->identifier = ContaoTwigUtil::getIdentifier($name);
        $this->extension = ContaoTwigUtil::getExtension($name);

        return $this;
    }

    /**
     * Also includes variant templates, e.g: "content_element/text/special" when
     * filtering for "content_element/text". If $exclusive is set to true, only
     * the variants will be output.
     */
    public function withVariants(bool $exclusive = false): self
    {
        $this->variants = true;
        $this->variantsExclusive = $exclusive;

        return $this;
    }

    /**
     * Also includes templates of a certain theme. Only one theme at a time can
     * be queried.
     */
    public function withTheme(string $themeSlug): self
    {
        $this->themeSlug = $themeSlug;

        return $this;
    }

    /**
     * Returns the result as template options.
     *
     * @return array<string, string>
     */
    public function asTemplateOptions(): array
    {
        $getSourceLabel = function (string $name): string {
            if (null !== ($themeSlug = $this->themeNamespace->match($name))) {
                return $this->translator->trans('MSC.templatesTheme', [$themeSlug], 'contao_default');
            }

            if (preg_match('/^@Contao_([^\/]+?)(?:Bundle)?\//', $name, $matches)) {
                return $matches[1];
            }

            return $this->translator->trans('MSC.global', [], 'contao_default');
        };

        $getCustomLabel = function (string $identifier, array $sourceLabels): string|null {
            if (!$this->translator->getCatalogue()->has($identifier, 'templates')) {
                return null;
            }

            return sprintf(
                '%s [%s • %s]',
                $this->translator->trans($identifier, [], 'templates'),
                $identifier,
                implode(', ', $sourceLabels),
            );
        };

        $getLabel = static fn (string $identifier, array $sourceLabels): string => sprintf(
            '%s [%s]',
            $identifier,
            implode(', ', $sourceLabels),
        );

        $options = [];

        foreach (array_keys(iterator_to_array($this->getIterator())) as $identifier) {
            $sourceLabels = array_map($getSourceLabel, $this->sources[$identifier]);
            $key = $identifier !== $this->identifier ? $identifier : '';

            $options[$key] = $getCustomLabel($identifier, $sourceLabels) ?? $getLabel($identifier, $sourceLabels);
        }

        // Make sure the default option is the first one (see #5719)
        ksort($options);

        return $options;
    }

    /**
     * @return \Generator<string, string>
     */
    public function getIterator(): \Generator
    {
        // Only include chains that contain at least one non-legacy template
        $chains = array_filter(
            $this->hierarchy->getInheritanceChains($this->themeSlug),
            static function (array $chain) {
                foreach (array_keys($chain) as $path) {
                    if ('html5' !== Path::getExtension($path, true)) {
                        return true;
                    }
                }

                return false;
            },
        );

        $this->sources = [];

        $matchIdentifier = function (string $identifier): bool {
            if (!$this->variantsExclusive && $this->identifier === $identifier) {
                return true;
            }

            if (!$this->variants) {
                return false;
            }

            return str_starts_with($identifier, "$this->identifier/");
        };

        foreach ($chains as $identifier => $chain) {
            if ($this->identifier && !$matchIdentifier($identifier)) {
                continue;
            }

            // The loader makes sure that all files grouped under one
            // identifier have the same extension
            $extension = ContaoTwigUtil::getExtension(array_key_first($chain));

            if (null !== $this->extension && $this->extension !== $extension) {
                continue;
            }

            $this->sources[$identifier] = array_values($chain);

            yield $identifier => $extension;
        }
    }

    public function count(): int
    {
        return iterator_count($this->getIterator());
    }
}
