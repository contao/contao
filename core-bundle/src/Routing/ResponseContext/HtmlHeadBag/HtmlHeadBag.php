<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag;

use Contao\ArrayUtil;
use Contao\CoreBundle\Routing\ResponseContext\HtmlTag;
use Contao\CoreBundle\String\HtmlAttributes;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\UnicodeString;

final class HtmlHeadBag
{
    public const TAG_TITLE = 'contao.title';

    public const TAG_ROBOTS = 'contao.meta.robots';

    public const TAG_DESCRIPTION = 'contao.meta.description';

    public const TAG_CANONICAL = 'contao.canonical';

    private const RAW_HEAD_PREFIX = 'contao.twig.head';

    private const RAW_STYLESHEET_PREFIX = 'contao.twig.stylesheets';

    private string $name = '';

    private string $title = '';

    private string $metaDescription = '';

    private string $metaRobots = 'index,follow';

    private string $canonicalUri = '';

    private array $keepParamsForCanonical = [];

    /**
     * @var list<HtmlAttributes>
     */
    private array $metaTags = [];

    /**
     * @var list<HtmlAttributes>
     */
    private array $linkTags = [];

    /**
     * @var array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}>
     */
    private array $tags = [];

    /**
     * @var array<string, array{tag: string, before: HtmlTag|string|null, after: HtmlTag|string|null}>
     */
    private array $rawHeadTags = [];

    /**
     * @var array<string, array{tag: string, before: HtmlTag|string|null, after: HtmlTag|string|null}>
     */
    private array $rawStyleSheetTags = [];

    /**
     * @var array<string, array{before?: HtmlTag|string, after?: HtmlTag|string}>
     */
    private array $ordering = [];

    /**
     * @var array<string, true>
     */
    private array $removedTags = [];

    private bool $canonicalEnabled = false;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getMetaDescription(): string
    {
        return $this->metaDescription;
    }

    public function setMetaDescription(string $metaDescription): self
    {
        $this->metaDescription = $metaDescription;

        return $this;
    }

    public function getMetaRobots(): string
    {
        return $this->metaRobots;
    }

    public function setMetaRobots(string $metaRobots): self
    {
        $this->metaRobots = $metaRobots;

        return $this;
    }

    public function setKeepParamsForCanonical(array $keepParamsForCanonical): self
    {
        $this->keepParamsForCanonical = $keepParamsForCanonical;

        return $this;
    }

    public function getKeepParamsForCanonical(): array
    {
        return $this->keepParamsForCanonical;
    }

    public function addKeepParamsForCanonical(string $param): self
    {
        $this->keepParamsForCanonical[] = $param;

        return $this;
    }

    public function setCanonicalUri(string $canonicalUri): self
    {
        $this->canonicalUri = $canonicalUri;

        return $this;
    }

    public function getCanonicalUri(): string
    {
        return $this->canonicalUri;
    }

    public function getCanonicalUriForRequest(Request $request): string
    {
        if ($this->canonicalUri) {
            // Make sure the custom URI is normalized as well
            return Request::create($this->canonicalUri)->getUri();
        }

        $params = [];

        foreach ($request->query->all() as $originalParam => $value) {
            foreach ($this->getKeepParamsForCanonical() as $param) {
                $regex = \sprintf('/^%s$/', str_replace('\*', '.*', preg_quote($param, '/')));

                if (preg_match($regex, (string) $originalParam)) {
                    $params[$originalParam] = $value;
                }
            }
        }

        $request = Request::create(
            $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo(),
            $request->getMethod(),
            $params,
        );

        return $request->getUri();
    }

    /**
     * @internal
     */
    public function setCanonicalEnabled(bool $canonicalEnabled): self
    {
        $this->canonicalEnabled = $canonicalEnabled;

        return $this;
    }

    public function getMetaTags(): array
    {
        return $this->metaTags;
    }

    public function setMetaTags(array $metaTags): self
    {
        $this->metaTags = $metaTags;

        return $this;
    }

    public function addMetaTag(HtmlAttributes $metaTag): self
    {
        $this->metaTags[] = $metaTag;

        return $this;
    }

    public function removeMetaTag(string $key, string $value): self
    {
        $this->metaTags = array_filter($this->metaTags, static fn (HtmlAttributes $metaTag): bool => ($metaTag[$key] ?? null) !== $value);

        return $this;
    }

    public function getLinkTags(): array
    {
        return $this->linkTags;
    }

    public function setLinkTags(array $linkTags): self
    {
        $this->linkTags = $linkTags;

        return $this;
    }

    public function addLinkTag(HtmlAttributes $linkTag): self
    {
        $this->linkTags[] = $linkTag;

        return $this;
    }

    public function removeLinkTag(string $key, string $value): self
    {
        $this->linkTags = array_filter($this->linkTags, static fn (HtmlAttributes $linkTag): bool => ($linkTag[$key] ?? null) !== $value);

        return $this;
    }

    /**
     * Uses the tag's suggested identifier if no explicit identifier is given.
     */
    public function add(HtmlTag $tag, string|null $identifier = null): self
    {
        $this->setEntry($identifier ?? $tag->getSuggestedIdentifier(), $tag, []);

        return $this;
    }

    /**
     * The reference can be an explicit identifier or a semantically matching tag.
     */
    public function addBefore(HtmlTag $tag, HtmlTag|string $reference, string|null $identifier = null): self
    {
        $this->setEntry(
            $identifier ?? $tag->getSuggestedIdentifier(),
            $tag,
            ['before' => $reference],
        );

        return $this;
    }

    /**
     * The reference can be an explicit identifier or a semantically matching tag.
     */
    public function addAfter(HtmlTag $tag, HtmlTag|string $reference, string|null $identifier = null): self
    {
        $this->setEntry(
            $identifier ?? $tag->getSuggestedIdentifier(),
            $tag,
            ['after' => $reference],
        );

        return $this;
    }

    /**
     * Adds trusted markup that cannot be represented by a single HtmlTag.
     */
    public function addRaw(string $identifier, string $markup): self
    {
        $this->setEntry($identifier, $markup, []);

        return $this;
    }

    /**
     * @internal
     *
     * @param array{before?: HtmlTag|string, after?: HtmlTag|string} $position
     */
    public function addRawToHead(string $identifier, string $markup, array $position = []): self
    {
        if ('' === $identifier) {
            throw new \InvalidArgumentException('The HTML head tag identifier must not be empty.');
        }

        $this->rawHeadTags[$identifier] = $this->createEntry($markup, $position);
        unset($this->removedTags[self::RAW_HEAD_PREFIX.'.'.$identifier]);

        return $this;
    }

    /**
     * @internal
     *
     * @param array{before?: HtmlTag|string, after?: HtmlTag|string} $position
     */
    public function addRawToStylesheets(string $identifier, string $markup, array $position = []): self
    {
        if ('' === $identifier) {
            throw new \InvalidArgumentException('The HTML head tag identifier must not be empty.');
        }

        $this->rawStyleSheetTags[$identifier] = $this->createEntry($markup, $position);
        unset($this->removedTags[self::RAW_STYLESHEET_PREFIX.'.'.$identifier]);

        return $this;
    }

    /**
     * Adds trusted markup before another tag.
     */
    public function addRawBefore(string $identifier, string $markup, HtmlTag|string $reference): self
    {
        $this->setEntry($identifier, $markup, ['before' => $reference]);

        return $this;
    }

    /**
     * Adds trusted markup after another tag.
     */
    public function addRawAfter(string $identifier, string $markup, HtmlTag|string $reference): self
    {
        $this->setEntry($identifier, $markup, ['after' => $reference]);

        return $this;
    }

    /**
     * Overrides the ordering constraint of an existing tag.
     */
    public function orderBefore(string $identifier, HtmlTag|string $reference): self
    {
        $this->ordering[$identifier] = ['before' => $reference];

        return $this;
    }

    /**
     * Overrides the ordering constraint of an existing tag.
     */
    public function orderAfter(string $identifier, HtmlTag|string $reference): self
    {
        $this->ordering[$identifier] = ['after' => $reference];

        return $this;
    }

    public function remove(string $identifier): self
    {
        $entries = $this->collectEntries(null);
        $resolvedIdentifier = $this->resolveStoredIdentifier($identifier, $entries);

        unset($this->tags[$resolvedIdentifier], $this->ordering[$identifier], $this->ordering[$resolvedIdentifier]);

        $this->removedTags[$resolvedIdentifier] = true;

        return $this;
    }

    public function has(string $identifier, Request|null $request = null): bool
    {
        $entries = $this->collectEntries($request);

        return isset($entries[$this->resolveStoredIdentifier($identifier, $entries)]);
    }

    /**
     * Returns the tags in rendering order. References to tags that do not exist
     * are ignored.
     *
     * @return array<string, HtmlTag|string>
     */
    public function all(Request|null $request = null): array
    {
        $entries = $this->collectEntries($request);
        $constraints = $this->resolveConstraints($entries);
        $tags = [];

        foreach (ArrayUtil::sortByOrderConstraints($entries, $constraints) as $identifier => $entry) {
            $tags[$identifier] = $entry['tag'];
        }

        return $tags;
    }

    /**
     * @param array{before?: HtmlTag|string, after?: HtmlTag|string} $position
     */
    private function setEntry(string $identifier, HtmlTag|string $tag, array $position): void
    {
        if ('' === $identifier) {
            throw new \InvalidArgumentException('The HTML head tag identifier must not be empty.');
        }

        $this->tags[$identifier] = $this->createEntry($tag, $position);

        unset($this->removedTags[$identifier]);
    }

    /**
     * @return array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}>
     */
    private function collectEntries(Request|null $request): array
    {
        $entries = $this->getCoreEntries($request);

        foreach ($this->tags as $identifier => $entry) {
            $entries[$identifier] = $entry;
        }

        $this->addRawEntries($entries, $this->rawStyleSheetTags, self::RAW_STYLESHEET_PREFIX);
        $this->addRawEntries($entries, $this->rawHeadTags, self::RAW_HEAD_PREFIX);

        foreach (array_keys($this->removedTags) as $identifier) {
            unset($entries[$identifier]);
        }

        return $entries;
    }

    /**
     * @return array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}>
     */
    private function getCoreEntries(Request|null $request): array
    {
        $entries = [
            self::TAG_TITLE => $this->createEntry(HtmlTag::title($this->title)),
            self::TAG_ROBOTS => $this->createEntry(HtmlTag::meta(['name' => 'robots', 'content' => $this->metaRobots])),
            self::TAG_DESCRIPTION => $this->createEntry(HtmlTag::meta(['name' => 'description', 'content' => new UnicodeString($this->metaDescription)->truncate(320, '…')])),
        ];

        $this->addAdditionalMetaEntries($entries);
        $this->addCanonicalEntry($entries, $request);
        $this->addAdditionalLinkEntries($entries);

        return $entries;
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     */
    private function addAdditionalMetaEntries(array &$entries): void
    {
        foreach ($this->metaTags as $key => $attributes) {
            $entries["contao.meta.additional.$key"] = $this->createEntry(HtmlTag::meta($attributes));
        }
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     */
    private function addCanonicalEntry(array &$entries, Request|null $request): void
    {
        if (!$this->canonicalEnabled || !$request) {
            return;
        }

        $entries[self::TAG_CANONICAL] = $this->createEntry(HtmlTag::link([
            'rel' => 'canonical',
            'href' => $this->getCanonicalUriForRequest($request),
        ]));
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     */
    private function addAdditionalLinkEntries(array &$entries): void
    {
        foreach ($this->linkTags as $key => $attributes) {
            $entries["contao.link.additional.$key"] = $this->createEntry(HtmlTag::link($attributes));
        }
    }

    /**
     * @param array{before?: HtmlTag|string, after?: HtmlTag|string} $position
     *
     * @return array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}
     */
    private function createEntry(HtmlTag|string $tag, array $position = []): array
    {
        return [
            'tag' => $tag,
            'before' => $position['before'] ?? null,
            'after' => $position['after'] ?? null,
        ];
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     * @param array<string, array{tag: string, before: HtmlTag|string|null, after: HtmlTag|string|null}>         $rawEntries
     */
    private function addRawEntries(array &$entries, array $rawEntries, string $prefix): void
    {
        foreach ($rawEntries as $identifier => $entry) {
            $entries["$prefix.$identifier"] = $entry;
        }
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     *
     * @return array<string, array{before: string|null, after: string|null}>
     */
    private function resolveConstraints(array $entries): array
    {
        $identifierMap = $this->collectIdentifierMap($entries);
        $constraints = [];

        foreach ($entries as $identifier => $entry) {
            $constraints[$identifier] = [
                'before' => $this->resolveReference($entry['before'], $entries, $identifierMap),
                'after' => $this->resolveReference($entry['after'], $entries, $identifierMap),
            ];
        }

        foreach ($this->ordering as $identifier => $ordering) {
            $identifier = $this->resolveReference($identifier, $entries, $identifierMap);

            if (null === $identifier || !isset($entries[$identifier])) {
                continue;
            }

            $constraints[$identifier] = [
                'before' => $this->resolveReference($ordering['before'] ?? null, $entries, $identifierMap),
                'after' => $this->resolveReference($ordering['after'] ?? null, $entries, $identifierMap),
            ];
        }

        return $constraints;
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     *
     * @return array<string, list<string>>
     */
    private function collectIdentifierMap(array $entries): array
    {
        $identifierMap = [];

        $this->addRawIdentifiers($identifierMap, $this->rawStyleSheetTags, self::RAW_STYLESHEET_PREFIX);
        $this->addRawIdentifiers($identifierMap, $this->rawHeadTags, self::RAW_HEAD_PREFIX);

        foreach ($entries as $identifier => $entry) {
            if ($entry['tag'] instanceof HtmlTag) {
                $identifierMap[$entry['tag']->getSuggestedIdentifier()][] = $identifier;
            }
        }

        return $identifierMap;
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     */
    private function resolveStoredIdentifier(string $identifier, array $entries): string
    {
        if (isset($entries[$identifier])) {
            return $identifier;
        }

        $matches = array_filter(
            [
                self::RAW_HEAD_PREFIX.'.'.$identifier,
                self::RAW_STYLESHEET_PREFIX.'.'.$identifier,
            ],
            static fn (string $match): bool => isset($entries[$match]),
        );

        if (1 < \count($matches)) {
            throw new \LogicException(\sprintf('The HTML tag identifier "%s" is ambiguous.', $identifier));
        }

        return $matches ? array_values($matches)[0] : $identifier;
    }

    /**
     * @param array<string, list<string>>                                                                $identifierMap
     * @param array<string, array{tag: string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $rawEntries
     */
    private function addRawIdentifiers(array &$identifierMap, array $rawEntries, string $prefix): void
    {
        foreach (array_keys($rawEntries) as $identifier) {
            $internalIdentifier = "$prefix.$identifier";

            if (!isset($this->removedTags[$internalIdentifier])) {
                $identifierMap[$identifier][] = $internalIdentifier;
            }
        }
    }

    /**
     * @param array<string, array{tag: HtmlTag|string, before: HtmlTag|string|null, after: HtmlTag|string|null}> $entries
     * @param array<string, list<string>>                                                                        $identifierMap
     */
    private function resolveReference(HtmlTag|string|null $reference, array $entries, array $identifierMap): string|null
    {
        if (null === $reference) {
            return $reference;
        }

        if (\is_string($reference) && isset($entries[$reference])) {
            return $reference;
        }

        $reference = $reference instanceof HtmlTag ? $reference->getSuggestedIdentifier() : $reference;

        $matches = $identifierMap[$reference] ?? [];

        if (1 < \count($matches)) {
            throw new \LogicException(\sprintf('The HTML tag reference "%s" is ambiguous.', $reference));
        }

        return $matches[0] ?? $reference;
    }
}
