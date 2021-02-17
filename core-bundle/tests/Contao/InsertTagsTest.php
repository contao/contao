<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Contao;

use Contao\CoreBundle\Image\Studio\FigureRenderer;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\InsertTags;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;

class InsertTagsTest extends TestCase
{
    /**
     * @dataProvider provideFigureInsertTags
     */
    public function testFigureInsertTag(string $input, array $expectedArguments): void
    {
        $usedArguments = [];

        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($this->once())
            ->method('render')
            ->willReturnCallback(
                static function (...$arguments) use (&$usedArguments) {
                    $usedArguments = $arguments;

                    return '<figure>foo</figure>';
                }
            )
        ;

        $this->setContainerWithContaoConfiguration([FigureRenderer::class => $figureRenderer]);

        $output = (new InsertTags())->replace($input, false);

        $this->assertSame('<figure>foo</figure>', $output);
        $this->assertSame($expectedArguments, $usedArguments);
    }

    public function provideFigureInsertTags(): \Generator
    {
        $defaultTemplate = '@ContaoCore/Image/Studio/figure.html.twig';

        yield 'without any configuration' => [
            '{{figure::123}}',
            ['123', null, [], $defaultTemplate],
        ];

        yield 'with size' => [
            '{{figure::files/cat.jpg?size=_my_size}}',
            ['files/cat.jpg', '_my_size', [], $defaultTemplate],
        ];

        yield 'with custom template' => [
            '{{figure::files/cat.jpg?template=foo.html.twig}}',
            ['files/cat.jpg', null, [], 'foo.html.twig'],
        ];

        yield 'with nested options' => [
            '{{figure::1000?size=5&metadata[title]=foo%20bar&options[attr][class]=baz}}',
            [
                '1000',
                5,
                [
                    'metadata' => ['title' => 'foo bar'],
                    'options' => ['attr' => ['class' => 'baz']],
                ],
                $defaultTemplate,
            ],
        ];

        yield 'complex configuration' => [
            '{{figure::files/foo.jpg?size=_my_size&metadata[alt]=alt&template=my_template.html.twig&enableLightbox=1}}',
            [
                'files/foo.jpg',
                '_my_size',
                [
                    'metadata' => ['alt' => 'alt'],
                    'enableLightbox' => 1,
                ],
                'my_template.html.twig',
            ],
        ];

        yield 'wrapped basic entities' => [
            '{{figure::123?size[]=800[&]size[]=600[&]metadata[alt]=alt[&]enableLightbox=1}}',
            [
                '123',
                [800, 600],
                [
                    'metadata' => ['alt' => 'alt'],
                    'enableLightbox' => 1,
                ],
                $defaultTemplate,
            ],
        ];

        yield 'HTML' => [
            '{{figure::123?metadata[caption]=<script>alert(1)</script>}}',
            [
                '123',
                null,
                [
                    'metadata' => ['caption' => '&lt;script&gt;alert(1)&lt;/script&gt;'],
                ],
                $defaultTemplate,
            ],
        ];

        yield 'urlencoded HTML' => [
            '{{figure::123?metadata[caption]=%3Cscript%3Ealert%281%29%3C%2Fscript%3E}}',
            [
                '123',
                null,
                [
                    'metadata' => ['caption' => '&lt;script&gt;alert(1)&lt;/script&gt;'],
                ],
                $defaultTemplate,
            ],
        ];
    }

    /**
     * @dataProvider provideInvalidFigureInsertTags
     */
    public function testFigureInsertTagReturnsEmptyStringIfInvalid(string $input, bool $invalidConfiguration): void
    {
        $figureRenderer = $this->createMock(FigureRenderer::class);
        $figureRenderer
            ->expects($invalidConfiguration ? $this->once() : $this->never())
            ->method('render')
            ->willThrowException(new \InvalidArgumentException('bad call'))
        ;

        $this->setContainerWithContaoConfiguration([FigureRenderer::class => $figureRenderer]);

        $output = (new InsertTags())->replace($input, false);

        $this->assertSame('', $output);
    }

    public function provideInvalidFigureInsertTags(): \Generator
    {
        yield 'missing resource' => [
            '{{figure}}', false,
        ];

        yield 'too many arguments' => [
            '{{figure::5?size=1::other}}', false,
        ];

        yield 'invalid configuration' => [
            '{{figure::1?foo=bar}}', true,
        ];
    }

    private function setContainerWithContaoConfiguration(array $configuration = []): void
    {
        $container = $this->getContainerWithContaoConfiguration();
        $container->set('request_stack', $this->createMock(RequestStack::class));
        $container->set('contao.security.token_checker', $this->createMock(TokenChecker::class));

        foreach ($configuration as $name => $value) {
            $container->set($name, $value);
        }

        System::setContainer($container);
    }
}
