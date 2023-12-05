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
use Contao\CoreBundle\Twig\Inheritance\TemplateHierarchyInterface;
use Contao\CoreBundle\Twig\Inspector\InspectorNodeVisitor;
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
                $this->createMock(TemplateHierarchyInterface::class),
                new InspectorNodeVisitor(new NullAdapter()),
                $this->createMock(ContaoCsrfTokenManager::class),
            ),
        );

        $environment->addTokenParser(new SlotTokenParser());
        $environment->setLoader(new ArrayLoader(['template.html.twig' => $code]));

        $this->assertSame($environment->render('template.html.twig', $context), $expectedOutput);
    }

    public function provideSources(): \Generator
    {
        yield 'no slots' => [
            ['foo' => 'bar'],
            '{{ foo }}',
            'bar',
        ];

        yield 'slot without content' => [
            ['foo' => 'bar'],
            '{{ foo }} {% slot foo %}',
            'bar ',
        ];

        yield 'slot with content' => [
            ['foo' => 'bar', '_slots' => ['foo' => 'baz']],
            '{{ foo }} {% slot foo %}',
            'bar baz',
        ];

        yield 'multiple slots' => [
            ['_slots' => ['foo' => 'foo', 'bar' => 'bar', 'baz' => 'baz']],
            '{% slot foo %} {% slot bar %} {% slot other %}',
            'foo bar ',
        ];

        yield 'slot context should not be accessible' => [
            ['_thing' => ['foo' => 'foo'], '_slots' => ['foo' => 'foo']],
            '{{ _thing["foo"]|default("-") }} {{ _slots["foo"]|default("-") }}',
            'foo -',
        ];

        yield 'slot content must be output raw' => [
            ['foo' => '&', '_slots' => ['foo' => '&']],
            '{{ foo }} {% slot foo %}',
            '&amp; &',
        ];
    }
}
