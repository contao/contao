<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Routing\ResponseContext;

use Contao\CoreBundle\String\HtmlAttributes;

final class HtmlTag
{
    private HtmlAttributes $attributes;

    private function __construct(
        private readonly string $name,
        private string|null $content = null,
        private bool $escapeContent = true,
    ) {
        if (!preg_match('/^[a-z][a-z0-9:-]*$/i', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid HTML tag name "%s".', $name));
        }

        $this->attributes = new HtmlAttributes();
    }

    public function __clone()
    {
        $this->attributes = clone $this->attributes;
    }

    /**
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function create(string $name, string|null $content = null, HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return new self(strtolower($name), $content)->withAttributes($attributes);
    }

    /**
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function title(string $title, HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return new self('title', $title)->withAttributes($attributes);
    }

    /**
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function meta(HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return new self('meta')->withAttributes($attributes);
    }

    /**
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function link(HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return new self('link')->withAttributes($attributes);
    }

    /**
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function stylesheet(string $href, HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return self::link(['rel' => 'stylesheet', 'href' => $href])
            ->withAttributes($attributes)
            ->withAttribute('rel', 'stylesheet')
            ->withAttribute('href', $href)
        ;
    }

    /**
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function script(string $src, HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return new self('script', escapeContent: false)
            ->withAttribute('src', $src)
            ->withAttributes($attributes)
            ->withAttribute('src', $src)
        ;
    }

    /**
     * Only pass trusted JavaScript to this method.
     *
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function inlineScript(string $content, HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return new self('script', $content, false)->withAttributes($attributes);
    }

    /**
     * Only pass trusted CSS to this method.
     *
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public static function inlineStyle(string $content, HtmlAttributes|iterable|string|null $attributes = null): self
    {
        return new self('style', $content, false)->withAttributes($attributes);
    }

    public function withAttribute(string $name, \Stringable|bool|float|int|string|null $value = true): self
    {
        $clone = clone $this;
        $clone->attributes->set($name, $value);

        return $clone;
    }

    /**
     * @param HtmlAttributes|iterable<string, \Stringable|bool|float|int|string|null>|string|null $attributes
     */
    public function withAttributes(HtmlAttributes|iterable|string|null $attributes): self
    {
        $clone = clone $this;
        $clone->attributes->mergeWith($attributes);

        return $clone;
    }

    public function withContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        $clone->escapeContent = true;

        return $clone;
    }

    /**
     * Only pass trusted HTML, CSS or JavaScript to this method.
     */
    public function withRawContent(string $content): self
    {
        $clone = clone $this;
        $clone->content = $content;
        $clone->escapeContent = false;

        return $clone;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAttributes(): HtmlAttributes
    {
        return clone $this->attributes;
    }

    public function getContent(): string|null
    {
        return $this->content;
    }

    public function escapesContent(): bool
    {
        return $this->escapeContent;
    }

    public function isInlineScript(): bool
    {
        return 'script' === $this->name && null !== $this->content && !isset($this->attributes['src']);
    }

    public function isInlineStyle(): bool
    {
        return 'style' === $this->name && null !== $this->content;
    }

    /**
     * Returns a deterministic semantic identifier suitable for ordering tags.
     */
    public function getSuggestedIdentifier(): string
    {
        if ('script' === $this->name && $identifier = $this->getAttributeIdentifier('src')) {
            return $identifier;
        }

        if ('link' === $this->name && $identifier = $this->getAttributeIdentifier('rel', 'href')) {
            return $identifier;
        }

        if ('meta' === $this->name) {
            foreach (['charset', 'name', 'property', 'http-equiv', 'itemprop'] as $attribute) {
                if ($identifier = $this->getAttributeIdentifier($attribute)) {
                    return $identifier;
                }
            }
        }

        if ('title' === $this->name) {
            return 'title';
        }

        $payload = json_encode(
            [$this->name, $this->attributes, $this->content, $this->escapeContent],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );

        return "{$this->name}[".hash('xxh3', $payload).']';
    }

    private function getAttributeIdentifier(string ...$attributes): string|null
    {
        $identifier = $this->name;

        foreach ($attributes as $attribute) {
            if (!isset($this->attributes[$attribute])) {
                return null;
            }

            $value = json_encode((string) $this->attributes[$attribute], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            $identifier .= "[$attribute=$value]";
        }

        return $identifier;
    }
}
