<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Twig\Inheritance;

use Contao\CoreBundle\Tests\TestCase;
use Contao\CoreBundle\Twig\Inheritance\RuntimeThemeDependentExpression;
use Twig\Compiler;
use Twig\Environment;

class RuntimeThemeDependentExpressionTest extends TestCase
{
    public function testCompilesExpressionCode(): void
    {
        $expression = new RuntimeThemeDependentExpression([
            'foo' => '@Contao_Theme_foo/element.html.twig',
            'bar' => '@Contao_Theme_bar/element.html.twig',
            '' => '@Contao_ContaoCoreBundle/element.html.twig',
        ]);

        $compiler = new Compiler($this->createMock(Environment::class));
        $expression->compile($compiler);

        $expectedSource = <<<'SOURCE'
            match($this->extensions[\Contao\CoreBundle\Twig\Extension\ContaoExtension::class]->getCurrentThemeSlug()) {'foo' => '@Contao_Theme_foo/element.html.twig', 'bar' => '@Contao_Theme_bar/element.html.twig', default => "@Contao_ContaoCoreBundle/element.html.twig"}
            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }

    public function testCompilesExpressionCodeWithSingleName(): void
    {
        $expression = new RuntimeThemeDependentExpression([
            '' => '@Contao_ContaoCoreBundle/element.html.twig',
        ]);

        $compiler = new Compiler($this->createMock(Environment::class));
        $expression->compile($compiler);

        $expectedSource = <<<'SOURCE'
            "@Contao_ContaoCoreBundle/element.html.twig"
            SOURCE;

        $this->assertSame($expectedSource, $compiler->getSource());
    }

    public function testValidatesMappingHasADefaultValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The value mapping needs a default value.');

        new RuntimeThemeDependentExpression([
            'foo' => '@Contao_Theme_foo/element.html.twig',
            'bar' => '@Contao_Theme_bar/element.html.twig',
        ]);
    }
}
