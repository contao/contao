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

use Contao\CoreBundle\DataContainer\DataContainerOperationsBuilder;
use Contao\CoreBundle\String\HtmlAttributes;
use Contao\CoreBundle\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

class DataContainerOperationBuilderTest extends TestCase
{
    #[DataProvider('parsesOperationsHtmlProvider')]
    public function testParsesOperationsHtml(string $html, array $expected): void
    {
        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/data_container/operations.html.twig',
                $this->callback(
                    static function (array $data) use ($expected) {
                        if (\count($data['operations']) !== \count($expected)) {
                            return false;
                        }

                        foreach ($data['operations'] as $i => $operation) {
                            if ($operation['html'] !== $expected[$i]) {
                                return false;
                            }
                        }

                        return true;
                    },
                ),
            )
            ->willReturn('success')
        ;

        $builder = new DataContainerOperationsBuilder(
            $this->createContaoFrameworkStub(),
            $twig,
            $this->createMock(Security::class),
            $this->createMock(UrlGeneratorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');
        $builder->append(['html' => $html], true);

        $this->assertSame('success', (string) $builder);
    }

    public static function parsesOperationsHtmlProvider(): iterable
    {
        yield [
            '<a href="#">foo</a>',
            ['<a href="#">foo</a>'],
        ];

        yield [
            '<img src="foo.svg" alt="foo"> Test',
            ['<img src="foo.svg" alt="foo"> Test'],
        ];

        yield [
            '<img src="pasteinto.svg" alt="Paste into"> <img src="pasteafter.svg" alt="Paste after"> ',
            ['<img src="pasteinto.svg" alt="Paste into">', '<img src="pasteafter.svg" alt="Paste after">'],
        ];

        yield [
            '<img src="pasteinto.svg" alt=""> Paste into <img src="pasteafter.svg" alt=""> Paste after ',
            ['<img src="pasteinto.svg" alt=""> Paste into ', '<img src="pasteafter.svg" alt=""> Paste after '],
        ];

        yield [
            '<a href="#"><img src="pasteinto.svg" alt="Paste into">foo</a> <img src="pasteafter.svg" alt="Paste after"> ',
            ['<a href="#"><img src="pasteinto.svg" alt="Paste into">foo</a>', '<img src="pasteafter.svg" alt="Paste after">'],
        ];

        yield [
            '<a href="#"><img src="pasteinto.svg" alt="Paste into">bar</a> foo',
            ['<a href="#"><img src="pasteinto.svg" alt="Paste into">bar</a>', ' foo'],
        ];

        yield [
            'foo <a href="#"><img src="pasteinto.svg" alt="Paste into">bar</a>',
            ['foo <a href="#"><img src="pasteinto.svg" alt="Paste into">bar</a>'],
        ];

        yield [
            '<a href="#"><img src="pasteinto.svg" alt="Einfügen">foo</a> <img src="pasteafter.svg" alt="Danach einfügen"> ',
            ['<a href="#"><img src="pasteinto.svg" alt="Einfügen">foo</a>', '<img src="pasteafter.svg" alt="Danach einfügen">'],
        ];
    }

    public function testRemovesEmptyHtml(): void
    {
        $expected = [['href' => 'foo', 'label' => 'foo', 'attributes' => new HtmlAttributes()]];

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/data_container/operations.html.twig',
                $this->callback(static fn (array $data) => $data['operations'] === $expected),
            )
            ->willReturn('success')
        ;

        $builder = new DataContainerOperationsBuilder(
            $this->createContaoFrameworkStub(),
            $twig,
            $this->createMock(Security::class),
            $this->createMock(UrlGeneratorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');
        $builder->append($expected[0]);
        $builder->append(['html' => ''], true);

        $this->assertSame('success', (string) $builder);
    }

    public function testRemovesDuplicateSeparators(): void
    {
        $expected = [
            ['href' => 'foo', 'label' => 'foo', 'attributes' => new HtmlAttributes()],
            ['separator' => true],
            ['href' => 'bar', 'label' => 'bar', 'attributes' => new HtmlAttributes()],
        ];

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with(
                '@Contao/backend/data_container/operations.html.twig',
                $this->callback(static fn (array $data) => $data['operations'] === $expected),
            )
            ->willReturn('success')
        ;

        $builder = new DataContainerOperationsBuilder(
            $this->createContaoFrameworkStub(),
            $twig,
            $this->createMock(Security::class),
            $this->createMock(UrlGeneratorInterface::class),
        );

        $builder = $builder->initialize('tl_foo');
        $builder->append($expected[0]);
        $builder->addSeparator();
        $builder->append(['html' => ''], true);
        $builder->addSeparator();
        $builder->append($expected[2]);

        $this->assertSame('success', (string) $builder);
    }
}
