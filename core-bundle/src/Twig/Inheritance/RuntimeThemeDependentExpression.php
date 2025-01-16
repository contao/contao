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
 * This expression evaluates the theme slug at runtime and resolves to a matching
 * value of the given value mapping.
 *
 * @experimental
 */
class RuntimeThemeDependentExpression extends AbstractExpression
{
    /**
     * @param array<string|int, string> $valuesByThemeSlug
     */
    public function __construct(array $valuesByThemeSlug)
    {
        $defaultValue = $valuesByThemeSlug[''] ?? throw new \InvalidArgumentException('The value mapping needs a default value.');
        unset($valuesByThemeSlug['']);

        parent::__construct([], [
            'default_value' => $defaultValue,
            'values_by_theme' => $valuesByThemeSlug,
        ]);
    }

    public function compile(Compiler $compiler): void
    {
        $valuesByThemeSlug = $this->getAttribute('values_by_theme');
        $defaultValue = $this->getAttribute('default_value');

        if (0 === \count($valuesByThemeSlug)) {
            $compiler->repr($this->getAttribute('default_value'));

            return;
        }

        /** @see RuntimeThemeExpressionTest::testCompilesExpressionCode() */
        $compiler->raw('match($this->extensions[\\Contao\\CoreBundle\\Twig\\Extension\\ContaoExtension::class]->getCurrentThemeSlug()) {');

        foreach ($valuesByThemeSlug as $theme => $value) {
            $compiler->raw("'$theme' => '$value', ");
        }

        $compiler
            ->raw('default => ')
            ->repr($defaultValue)
            ->raw('}')
        ;
    }
}
