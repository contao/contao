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
    private string $name = '';

    public function getName(): string
    {
        return $this->name;
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
        preg_match('/\.(\w+)(\.twig)?/', $this->name, $matches);

        return strtolower($matches[1] ?? '');
    }

    /**
     * Check if the template matches the given types.
     *
     * Example for foo.html.twig:
     *   matchType('svg') // false
     *   matchType('html', 'html5') // true
     */
    public function matchType(string ...$types): bool
    {
        return \in_array($this->getType(), $types, true);
    }

    public function isContaoTemplate(): bool
    {
        return null !== ContaoTwigUtil::parseContaoName($this->name);
    }
}
