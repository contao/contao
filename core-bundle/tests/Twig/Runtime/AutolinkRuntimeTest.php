<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Runtime;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\AutolinkRuntime;
use PHPUnit\Framework\Attributes\DataProvider;

class AutolinkRuntimeTest extends TestCase
{
    #[DataProvider('provideText')]
    public function testLinkUrls(string $text, string $expected): void
    {
        /** @var AutolinkRuntime $runtime */
        $runtime = $this->getContainerWithContaoConfiguration()->get('contao.twig.autolink_runtime');
        $actual = $runtime->linkUrls($text);
        $this->assertSame($expected, $actual);
    }

    public static function provideText(): iterable
    {
        yield 'simple comment' => [
            'Also see https://example.com for the full setup guide.',
            'Also see <a href="https://example.com" target="_blank" rel="noreferrer noopener">https://example.com</a> for the full setup guide.',
        ];

        yield 'encoded html' => [
            'Also see &lt;b&gt;https://example.com&lt;/b&gt; for the full setup guide.',
            'Also see &lt;b&gt;<a href="https://example.com" target="_blank" rel="noreferrer noopener">https://example.com</a>&lt;/b&gt; for the full setup guide.',
        ];

        yield 'no links' => [
            'Hello there! No links here.',
            'Hello there! No links here.',
        ];
    }
}
