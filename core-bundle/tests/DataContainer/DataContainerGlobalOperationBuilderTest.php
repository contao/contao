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

use Contao\CoreBundle\DataContainer\DataContainerGlobalOperationsBuilder;
use Contao\CoreBundle\Tests\TestCase;
use Contao\System;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class DataContainerGlobalOperationBuilderTest extends TestCase
{
    public function testThrowsExceptionIfNotInitialized(): void
    {
        $this->expectException(\RuntimeException::class);

        $builder = new DataContainerGlobalOperationsBuilder($this->mockContaoFramework(), $this->createMock(Environment::class), $this->createMock(UrlGeneratorInterface::class));
        $builder->append([]);
    }

    public function testRendersNothingWithoutOperations(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->never())
            ->method('render')
        ;

        $builder = new DataContainerGlobalOperationsBuilder($this->mockContaoFramework(), $twig, $this->createMock(UrlGeneratorInterface::class));
        $builder = $builder->initialize('tl_foo');

        $this->assertSame('', (string) $builder);
    }

    #[DataProvider('backButtonHrefProvider')]
    public function testBackButtonHref(string|null $href, string $expected): void
    {
        $GLOBALS['TL_LANG']['MSC'] = ['backBT' => 'Back', 'backBTTitle' => 'Back'];

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

        $builder = new DataContainerGlobalOperationsBuilder($framework, $twig, $urlGenerator);
        $builder = $builder->initialize('tl_foo');
        $builder->addBackButton($href);

        $this->assertSame('', (string) $builder);

        unset($GLOBALS['TL_LANG']['MSC']);
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
}
