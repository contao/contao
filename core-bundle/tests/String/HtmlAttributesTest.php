<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\String;

use Contao\CoreBundle\String\HtmlAttributes;

class HtmlAttributesTest extends AbstractHtmlAttributesTestCase
{
    protected function createAttributes(HtmlAttributes|iterable|string|null $attributes = null): HtmlAttributes
    {
        return new HtmlAttributes($attributes);
    }

    protected function assertSameAttributes(array $expected, HtmlAttributes $attributes, string $message = ''): void
    {
        $this->assertSame(array_filter($expected, static fn ($value) => false !== $value), iterator_to_array($attributes), $message);
    }
}
