<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Functional;

use Contao\TestCase\FunctionalTestCase;

class TwigTest extends FunctionalTestCase
{
    /**
     * @dataProvider getSanitizeHtmlFilter
     */
    public function testSanitizeHtmlFilter(string $source, string $expected): void
    {
        $twig = static::getContainer()->get('twig');

        $this->assertSame(
            $expected,
            $twig->render($twig->createTemplate('{{ input|sanitize_html }}'), ['input' => $source]),
        );
    }

    public function getSanitizeHtmlFilter(): \Generator
    {
        yield [
            'foo',
            'foo',
        ];

        yield [
            '{{insert_content::1}}',
            '&#123;&#123;insert_content::1&#125;&#125;',
        ];

        yield [
            '<span title="{{insert_content::1}}"></span>',
            '<span title="&#123;&#123;insert_content::1&#125;&#125;"></span>',
        ];

        yield [
            '<span title="{{foo">bar}}</span>',
            '<span title="&#123;&#123;foo">bar&#125;&#125;</span>',
        ];

        yield [
            '{<script></script>{insert_content::1}<script></script>}',
            '&#123;&#123;insert_content::1&#125;&#125;',
        ];

        yield [
            'foo<script>alert(1)</script>bar',
            'foobar',
        ];

        yield [
            '<b>foo</b> bar <span onmouseover="alert(1)">baz</span>',
            '<b>foo</b> bar <span>baz</span>',
        ];
    }
}
