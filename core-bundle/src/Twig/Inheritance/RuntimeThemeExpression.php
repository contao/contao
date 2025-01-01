<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Twig\Inheritance;

use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;

/**
 * @experimental
 */
class RuntimeThemeExpression extends AbstractExpression
{
    public function __construct(array $valuesByTheme)
    {
        $defaultValue = $valuesByTheme[''] ?? throw new \InvalidArgumentException('The value mapping needs a default value.');
        unset($valuesByTheme['']);

        parent::__construct([], [
            'default_value' => $defaultValue,
            'values_by_theme' => $valuesByTheme,
        ]);
    }

    public function compile(Compiler $compiler): void
    {
        $valuesByTheme = $this->getAttribute('values_by_theme');
        $defaultValue = $this->getAttribute('default_value');

        if (0 === \count($valuesByTheme)) {
            $compiler->repr($this->getAttribute('default_value'));

            return;
        }

        /** @see RuntimeThemeExpressionTest::testCompilesExpressionCode() */
        $compiler
            ->raw('match($this->extensions[\\Contao\\CoreBundle\\Twig\\Extension\\ContaoExtension::class]->getCurrentThemeSlug()) {')
        ;

        foreach ($valuesByTheme as $theme => $value) {
            $compiler->raw("'$theme' => '$value', ");
        }

        $compiler
            ->raw('default => ')
            ->repr($defaultValue)
            ->raw('}')
        ;
    }
}
