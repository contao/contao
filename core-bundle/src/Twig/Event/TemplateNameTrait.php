<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Event;

use Contao\CoreBundle\Twig\ContaoTwigUtil;

/**
 * @internal
 */
trait TemplateNameTrait
{
    private ?string $name = null;
    private ?string $contaoNamespace = null;
    private ?string $contaoShortName = null;

    /**
     * Returns the full logical name, e.g. "@Foo/bar.html.twig".
     */
    public function getName(): string
    {
        return $this->name ?? '';
    }

    /**
     * Returns true if the template comes from a managed "@Contao" or
     * "@Contao_*" namespace.
     */
    public function isContaoTemplate(): bool
    {
        return null !== $this->contaoNamespace;
    }

    /**
     * Returns the Contao namespace or an empty string if not applicable, e.g.
     * "@Contao_Theme_foo" or "@Contao".
     */
    public function getContaoNamespace(): string
    {
        return $this->contaoNamespace ?? '';
    }

    /**
     * Returns the short name, e.g. "foo.html.twig" for a Contao Twig template
     * "@Contao/foo.html.twig".
     */
    public function getContaoShortName(): string
    {
        return $this->contaoShortName ?? '';
    }

    /**
     * Returns the template's file extension but strips off a .twig suffix if
     * it exists. The returned string will always be lowercase.
     *
     * Example:
     *  getType('foo.html.twig') // 'html'
     *  getType('bar.html5') // 'html5'
     *  getType('foobar') // ''
     */
    public function getType(): string
    {
        preg_match('/\.(\w+)(\.twig)?/', $this->getName(), $matches);

        return strtolower($matches[1] ?? '');
    }

    /**
     * Checks if the template matches the given types.
     *
     * Example for foo.html.twig:
     *   matchType('svg') // false
     *   matchType('html', 'html5') // true
     */
    public function matchType(string ...$types): bool
    {
        if ('' === ($type = $this->getType())) {
            return false;
        }

        return \in_array($type, $types, true);
    }

    private function setName(string $name): void
    {
        $this->name = $name;
        $result = ContaoTwigUtil::parseContaoName($name);

        if (null !== $result) {
            [$this->contaoNamespace, $this->contaoShortName] = $result;
        }
    }
}
