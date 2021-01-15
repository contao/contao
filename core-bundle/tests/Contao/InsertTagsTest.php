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

use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Runtime\FigureRendererRuntime;
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
        $figureRendererRuntime = $this->createMock(FigureRendererRuntime::class);
        $figureRendererRuntime
            ->expects($this->once())
            ->method('render')
            ->with(...$expectedArguments)
            ->willReturn('<figure>foo</figure>')
        ;

        $this->setContainerWithContaoConfiguration([
            FigureRendererRuntime::class => $figureRendererRuntime,
        ]);

        $output = (new InsertTags())->replace($input, false);

        $this->assertSame('<figure>foo</figure>', $output);
    }

    public function provideFigureInsertTags(): \Generator
    {
        yield 'without any configuration' => [
            '{{figure::123}}',
            ['123', null, []],
        ];

        yield 'with size' => [
            '{{figure::files/cat.jpg?size=_my_size}}',
            ['files/cat.jpg', '_my_size', []],
        ];

        yield 'with nested options' => [
            '{{figure::1000?size=5&metadata[title]=foo%20bar&options[attr][class]=baz&enableLightbox=1}}',
            [
                '1000',
                '5',
                [
                    'metadata' => ['title' => 'foo bar'],
                    'options' => ['attr' => ['class' => 'baz']],
                    'enableLightbox' => '1',
                ],
            ],
        ];

        yield 'with custom template' => [
            '{{figure::files/foo.jpg::customTemplate.html.twig}}',
            ['files/foo.jpg', null, [], 'customTemplate.html.twig'],
        ];
    }

    /**
     * @dataProvider provideInvalidFigureInsertTags
     */
    public function testFigureInsertTagReturnsEmptyStringIfInvalid(string $input, bool $invalidConfiguration): void
    {
        $figureRendererRuntime = $this->createMock(FigureRendererRuntime::class);
        $figureRendererRuntime
            ->expects($invalidConfiguration ? $this->once() : $this->never())
            ->method('render')
            ->willThrowException(new \InvalidArgumentException('bad call'))
        ;

        $this->setContainerWithContaoConfiguration([
            FigureRendererRuntime::class => $figureRendererRuntime,
        ]);

        $output = (new InsertTags())->replace($input, false);

        $this->assertSame('', $output);
    }

    public function provideInvalidFigureInsertTags(): \Generator
    {
        yield 'missing resource' => [
            '{{figure}}', false,
        ];

        yield 'too many arguments' => [
            '{{figure::5?size=1::foo.html.twig::other}}', false,
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
