<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Slots;

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Extension\ContaoExtension;
use Contao\CoreBundle\Twig\Global\ContaoVariable;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Slots\SlotTokenParser;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Loader\LoaderInterface;

class SlotTokenParserTest extends TestCase
{
    public function testGetTag(): void
    {
        $tokenParser = new SlotTokenParser();

        $this->assertSame('slot', $tokenParser->getTag());
    }

    /**
     * @dataProvider provideSources
     */
    public function testOutputsSlots(array $context, string $code, string $expectedOutput): void
    {
        $environment = new Environment($this->createMock(LoaderInterface::class));

        $environment->addExtension(
            new ContaoExtension(
                $environment,
                $this->createMock(ContaoFilesystemLoader::class),
                $this->createMock(ContaoCsrfTokenManager::class),
                $this->createMock(ContaoVariable::class),
                new InspectorNodeVisitor(new NullAdapter(), $environment),
            ),
        );

        $environment->addTokenParser(new SlotTokenParser());
        $environment->setLoader(new ArrayLoader(['template.html.twig' => $code]));

        $this->assertSame($expectedOutput, $environment->render('template.html.twig', $context));
    }

    public static function provideSources(): iterable
    {
        yield 'slot with default body and no content' => [
            ['foo' => 'bar'],
            '{% slot foo %}{% endslot %}',
            '',
        ];

        yield 'slot with default body and assigned content' => [
            ['_slots' => ['foo' => 'bar']],
            '{% slot foo %}{% endslot %}',
            'bar',
        ];

        yield 'slot with defined body and no content' => [
            [],
            '{% slot foo %}<main>{{ slot() }}</main>{% endslot %}',
            '',
        ];

        yield 'slot with defined body and assigned content' => [
            ['_slots' => ['foo' => '<b>ar</b>']],
            '{% slot foo %}<main>{{ slot() }}</main>{% endslot %}',
            '<main><b>ar</b></main>',
        ];

        yield 'slot with fallback body and no content' => [
            [],
            '{% slot foo %}<main>{{ slot() }}</main>{% else %}<!-- nothing -->{% endslot %}',
            '<!-- nothing -->',
        ];

        yield 'slot with fallback body and assigned content' => [
            ['_slots' => ['foo' => 'bar']],
            '{% slot foo %}<main>{{ slot() }}</main>{% else %}<!-- nothing -->{% endslot %}',
            '<main>bar</main>',
        ];

        yield 'slot inside slot' => [
            ['_slots' => ['a' => 'A', 'b' => 'B']],
            '{% slot a %}<main>{{ slot() }}{% slot b %}{% endslot %}</main>{% endslot %}',
            '<main>AB</main>',
        ];

        yield 'slot inside slot with recursion' => [
            ['_slots' => ['a' => 'A', 'b' => 'B']],
            '{% slot a %}<a>{{ slot() }}{% slot b %}<b>{% slot a %}{% endslot %}</b>{% endslot %}</a>{% endslot %}',
            '<a>A<b>A</b></a>',
        ];

        yield 'assigning virtual slot function' => [
            ['_slots' => ['foo' => 'bar']],
            '{% slot foo %}{% set var = slot() %}<main>{{ var ~ " baz" }}</main>{% endslot %}',
            '<main>bar baz</main>',
        ];
    }
}
