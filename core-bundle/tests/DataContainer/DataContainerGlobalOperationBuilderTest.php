<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\DataContainer;

use Contao\Backend;
use Contao\Controller;
use Contao\CoreBundle\DataContainer\DataContainerGlobalOperationsBuilder;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class DataContainerGlobalOperationBuilderTest extends TestCase
{
    public function testThrowsExceptionIfNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);

        $builder = new DataContainerGlobalOperationsBuilder(
            $this->mockContaoFramework(),
            $this->createMock(Environment::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
        );

        $builder->append(['html' => '']);
    }

    public function testRendersNothingWithoutOperations(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render')
        ;

        $builder = new DataContainerGlobalOperationsBuilder(
            $this->mockContaoFramework(),
            $twig,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');

        $this->assertSame('', (string) $builder);
    }

    #[DataProvider('backButtonHrefProvider')]
    public function testBackButtonHref(string|null $href, string $expected): void
    {
        $systemAdapter = $this->mockAdapter(['getReferer']);
        $systemAdapter
            ->expects(null === $href ? $this->once() : $this->never())
            ->method('getReferer')
            ->willReturn($expected)
        ;

        $framework = $this->mockContaoFramework([System::class => $systemAdapter]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/data_container/global_operations.html.twig',
                $this->callback(static fn (array $parameters) => isset($parameters['operations'])
                        && 1 === \count($parameters['operations'])
                        && $parameters['operations'][0]['href'] === $expected,
                ),
            )
            ->willReturn('')
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->method('generate')
            ->willReturn('/contao')
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap([
                ['MSC.backBT', [], 'contao_default', 'Back'],
                ['MSC.backBTTitle', [], 'contao_default', 'Back Title'],
            ])
        ;

        $builder = new DataContainerGlobalOperationsBuilder($framework, $twig, $urlGenerator, $translator);
        $builder = $builder->initialize('tl_foo');
        $builder->addBackButton($href);

        $this->assertSame('', (string) $builder);
    }

    public static function backButtonHrefProvider(): \Generator
    {
        yield 'Use referer for back link' => [
            null,
            'foo/bar',
        ];

        yield 'Back link with query parameters only' => [
            'foo=bar&bar=foo',
            '/contao?foo=bar&bar=foo',
        ];

        yield 'Back link with absolute URL' => [
            '/contao/some-controller',
            '/contao/some-controller',
        ];
    }

    public function testAddClearClipboardButton(): void
    {
        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->expects($this->once())
            ->method('addToUrl')
            ->willReturnArgument(0)
        ;

        $framework = $this->mockContaoFramework([Backend::class => $backendAdapter]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/data_container/global_operations.html.twig',
                $this->callback(static fn (array $parameters) => isset($parameters['operations'])
                    && 1 === \count($parameters['operations'])
                    && 'clipboard=1' === $parameters['operations'][0]['href']
                    && ' class="header_clipboard" accesskey="x"' === (string) $parameters['operations'][0]['attributes'],
                ),
            )
            ->willReturn('')
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(1))
            ->method('trans')
            ->with('MSC.clearClipboard', [], 'contao_default')
            ->willReturn('Clear Clipboard')
        ;

        $builder = new DataContainerGlobalOperationsBuilder($framework, $twig, $this->createMock(UrlGeneratorInterface::class), $translator);
        $builder = $builder->initialize('tl_foo');
        $builder->addClearClipboardButton();

        $this->assertSame('', (string) $builder);
    }

    public function testAddNewButton(): void
    {
        $GLOBALS['TL_LANG']['DCA']['new'] = ['New Label', 'New Title'];

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/data_container/global_operations.html.twig',
                $this->callback(static fn (array $parameters) => isset($parameters['operations'])
                    && 1 === \count($parameters['operations'])
                    && 'foo=bar' === $parameters['operations'][0]['href']
                    && ' class="header_new" accesskey="n" data-action="contao--scroll-offset#store"' === (string) $parameters['operations'][0]['attributes'],
                ),
            )
            ->willReturn('')
        ;

        $builder = new DataContainerGlobalOperationsBuilder(
            $this->mockContaoFramework(),
            $twig,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');
        $builder->addNewButton('foo=bar');

        $this->assertSame('', (string) $builder);

        unset($GLOBALS['TL_LANG']);
    }

    public function testDoesNotRenderIfThereAreNoGlobalOperations(): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = [
            'list' => [],
        ];

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render')
        ;

        $builder = new DataContainerGlobalOperationsBuilder(
            $this->mockContaoFramework(),
            $twig,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');
        $builder->addGlobalButtons($this->createMock(DataContainer::class));

        $this->assertSame('', (string) $builder);

        unset($GLOBALS['TL_DCA']);
    }

    #[DataProvider('addGlobalButtonsProvider')]
    public function testAddGlobalButtons(array $dca, callable $expected, int $routes = 0, bool $selectView = false): void
    {
        $GLOBALS['TL_DCA']['tl_foo'] = $dca;

        $inputAdapter = $this->mockAdapter(['get']);
        $inputAdapter
            ->method('get')
            ->with('act')
            ->willReturn($selectView ? 'select' : '')
        ;

        $backendAdapter = $this->mockAdapter(['addToUrl']);
        $backendAdapter
            ->method('addToUrl')
            ->willReturnCallback(static fn (string $href, $blnAddRef, $arrUnset, $addRequestToken) => '/contao?'.$href.($addRequestToken ? '&rt=1234' : ''))
        ;

        $imageAdapter = $this->mockAdapter(['getPath']);
        $imageAdapter
            ->method('getPath')
            ->willReturnArgument(0)
        ;

        $controllerAdapter = $this->mockAdapter(['addAssetsUrlTo']);
        $controllerAdapter
            ->method('addAssetsUrlTo')
            ->willReturnArgument(0)
        ;

        $framework = $this->mockContaoFramework([
            Input::class => $inputAdapter,
            Backend::class => $backendAdapter,
            Image::class => $imageAdapter,
            Controller::class => $controllerAdapter,
        ]);

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/data_container/global_operations.html.twig',
                $this->callback($expected),
            )
            ->willReturn('')
        ;

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects($this->exactly($routes))
            ->method('generate')
            ->willReturnArgument(0)
        ;

        $builder = new DataContainerGlobalOperationsBuilder(
            $framework,
            $twig,
            $urlGenerator,
            $this->createMock(TranslatorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');
        $builder->addGlobalButtons($this->createMock(DataContainer::class));

        $this->assertSame('', (string) $builder);

        unset($GLOBALS['TL_DCA']);
    }

    public static function addGlobalButtonsProvider(): iterable
    {
        yield 'Adds href with query parameters to backend URL' => [
            [
                'list' => [
                    'global_operations' => [
                        'foo' => [
                            'href' => 'foo=bar',
                        ],
                    ],
                ],
            ],
            static fn (array $parameters) => isset($parameters['operations'])
                && 1 === \count($parameters['operations'])
                && '/contao?foo=bar&rt=1234' === $parameters['operations'][0]['href'],
        ];

        yield 'Does not change href with path' => [
            [
                'list' => [
                    'global_operations' => [
                        'foo' => [
                            'href' => '/contao/foo?bar=baz',
                        ],
                    ],
                ],
            ],
            static fn (array $parameters) => isset($parameters['operations'])
                && 1 === \count($parameters['operations'])
                && '/contao/foo?bar=baz' === $parameters['operations'][0]['href'],
        ];

        yield 'Generates route for href' => [
            [
                'list' => [
                    'global_operations' => [
                        'foo' => [
                            'route' => 'foobar',
                        ],
                    ],
                ],
            ],
            static fn (array $parameters) => isset($parameters['operations'])
                && 1 === \count($parameters['operations'])
                && 'foobar' === $parameters['operations'][0]['href'],
            1,
        ];

        yield 'Adds header icon' => [
            [
                'list' => [
                    'global_operations' => [
                        'foo' => [
                            'href' => '/foo/bar',
                            'icon' => '/foo/icon.svg',
                        ],
                    ],
                ],
            ],
            static fn (array $parameters) => isset($parameters['operations'])
                && 1 === \count($parameters['operations'])
                && ' class="foo header_icon" style="background-image: url(&apos;/foo/icon.svg&apos;);"' === (string) $parameters['operations'][0]['attributes'],
            0,
        ];
    }

    public function testThrowsExceptionIfBuilderIsInitializedTwice(): void
    {
        $this->expectException(\RuntimeException::class);

        $builder = new DataContainerGlobalOperationsBuilder(
            $this->createMock(ContaoFramework::class),
            $this->createMock(Environment::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');
        $builder->initialize('tl_bar');
    }
}
