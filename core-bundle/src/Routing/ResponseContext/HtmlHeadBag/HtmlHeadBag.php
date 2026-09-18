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
     * @var list<HtmlTag>
     */
    private array $tags = [];

    /**
     * @var array<array-key, string>
     */
    private array $rawHeadTags = [];

    /**
     * @var array<array-key, string>
     */
    private array $rawStylesheetTags = [];

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

    public function add(HtmlTag $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }

    /**
     * @internal
     */
    public function addRawToHead(string $markup, string|null $identifier = null): self
    {
        $this->addRawContent($this->rawHeadTags, $markup, $identifier);

        return $this;
    }

    /**
     * @internal
     */
    public function addRawToStylesheets(string $markup, string|null $identifier = null): self
    {
        $this->addRawContent($this->rawStylesheetTags, $markup, $identifier);

        return $this;
    }

    /**
     * @return array<array-key, HtmlTag|string>
     */
    public function all(Request|null $request = null): array
    {
        $tags = [
            self::TAG_TITLE => HtmlTag::title($this->title),
            self::TAG_ROBOTS => HtmlTag::meta(['name' => 'robots', 'content' => $this->metaRobots]),
            self::TAG_DESCRIPTION => HtmlTag::meta(['name' => 'description', 'content' => new UnicodeString($this->metaDescription)->truncate(320, '…')]),
        ];

        foreach ($this->metaTags as $key => $attributes) {
            $tags["contao.meta.additional.$key"] = HtmlTag::meta($attributes);
        }

        if ($this->canonicalEnabled && $request) {
            $tags[self::TAG_CANONICAL] = HtmlTag::link(['rel' => 'canonical', 'href' => $this->getCanonicalUriForRequest($request)]);
        }

        foreach ($this->linkTags as $key => $attributes) {
            $tags["contao.link.additional.$key"] = HtmlTag::link($attributes);
        }

        array_push($tags, ...$this->tags);

        foreach ($this->rawStylesheetTags as $identifier => $markup) {
            $tags["contao.twig.stylesheets.$identifier"] = $markup;
        }

        foreach ($this->rawHeadTags as $identifier => $markup) {
            $tags["contao.twig.head.$identifier"] = $markup;
        }

        return $tags;
    }

    /**
     * @param array<array-key, string> $content
     */
    private function addRawContent(array &$content, string $markup, string|null $identifier): void
    {
        if (null === $identifier) {
            $content[] = $markup;
        } else {
            $content[$identifier] = $markup;
        }
    }
}
