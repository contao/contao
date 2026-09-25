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

use Contao\ArrayUtil;

/**
 * @implements \IteratorAggregate<array-key, HtmlTag|string>
 */
final class HtmlBodyBag implements \IteratorAggregate
{
    /**
     * @var array<array-key, HtmlTag|string>
     */
    private array $content = [];

    /**
     * @var array<array-key, array{before?: HtmlTag|array-key, after?: HtmlTag|array-key}>
     */
    private array $constraints = [];

    /**
     * Adds trusted markup to the end of the HTML body. Structured tags use their
     * suggested identifier if no explicit identifier is given.
     */
    public function add(HtmlTag|string $content, string|null $identifier = null): self
    {
        $identifier ??= $content instanceof HtmlTag ? $content->getSuggestedIdentifier() : null;

        if (null === $identifier) {
            $this->content[] = $content;
        } else {
            $this->content[$identifier] = $content;
            unset($this->constraints[$identifier]);
        }

        return $this;
    }

    /**
     * The reference can be an explicit identifier or a semantically matching tag.
     */
    public function addBefore(HtmlTag|string $content, HtmlTag|string $reference, string|null $identifier = null): self
    {
        $identifier = $this->getIdentifier($content, $identifier);
        $this->content[$identifier] = $content;
        $this->constraints[$identifier] = ['before' => $reference];

        return $this;
    }

    /**
     * The reference can be an explicit identifier or a semantically matching tag.
     */
    public function addAfter(HtmlTag|string $content, HtmlTag|string $reference, string|null $identifier = null): self
    {
        $identifier = $this->getIdentifier($content, $identifier);
        $this->content[$identifier] = $content;
        $this->constraints[$identifier] = ['after' => $reference];

        return $this;
    }

    /**
     * Overrides the ordering constraint of existing content.
     */
    public function orderBefore(int|string $identifier, HtmlTag|int|string $reference): self
    {
        $this->constraints[$identifier] = ['before' => $reference];

        return $this;
    }

    /**
     * Overrides the ordering constraint of existing content.
     */
    public function orderAfter(int|string $identifier, HtmlTag|int|string $reference): self
    {
        $this->constraints[$identifier] = ['after' => $reference];

        return $this;
    }

    /**
     * @return array<array-key, HtmlTag|string>
     */
    public function all(): array
    {
        return ArrayUtil::sortByOrderConstraints($this->content, $this->resolveConstraints());
    }

    /**
     * @return \ArrayIterator<array-key, HtmlTag|string>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->all());
    }

    private function getIdentifier(HtmlTag|string $content, string|null $identifier): string
    {
        if (null !== $identifier) {
            return $identifier;
        }

        if ($content instanceof HtmlTag) {
            return $content->getSuggestedIdentifier();
        }

        throw new \InvalidArgumentException('An identifier is required to order raw HTML body content.');
    }

    /**
     * @return array<array-key, array{before?: array-key, after?: array-key}>
     */
    private function resolveConstraints(): array
    {
        $aliases = [];
        $constraints = $this->constraints;

        foreach ($this->content as $identifier => $content) {
            if ($content instanceof HtmlTag) {
                $aliases[$content->getSuggestedIdentifier()][] = $identifier;
            }
        }

        foreach ($constraints as $identifier => $constraint) {
            foreach (['before', 'after'] as $position) {
                if (isset($constraint[$position])) {
                    $constraints[$identifier][$position] = $this->resolveReference($constraint[$position], $aliases);
                }
            }
        }

        return $constraints;
    }

    /**
     * @param array<string, list<array-key>> $aliases
     */
    private function resolveReference(HtmlTag|int|string $reference, array $aliases): int|string
    {
        if (!$reference instanceof HtmlTag && \array_key_exists($reference, $this->content)) {
            return $reference;
        }

        $reference = $reference instanceof HtmlTag ? $reference->getSuggestedIdentifier() : $reference;

        $matches = $aliases[$reference] ?? [];

        if (1 < \count($matches)) {
            throw new \LogicException(\sprintf('The HTML tag reference "%s" is ambiguous.', $reference));
        }

        return $matches[0] ?? $reference;
    }
}
