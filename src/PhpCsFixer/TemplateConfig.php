<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Contao\PhpCsFixer;

use PhpCsFixer\Finder;

class TemplateConfig extends DefaultConfig
{
    public function getName(): string
    {
        return 'Contao template';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        $this->adjustSymfonyRules($rules);
        $this->adjustPhpCsFixerRules($rules);
        $this->adjustPhp71MigrationRules($rules);
        $this->adjustOtherRules($rules);

        return $rules;
    }

    public function getFinder(): Finder
    {
        return (new Finder())
            ->name('*.html5')
            ->in([
                __DIR__.'/../../calendar-bundle/src/Resources/contao/templates',
                __DIR__.'/../../comments-bundle/src/Resources/contao/templates',
                __DIR__.'/../../core-bundle/src/Resources/contao/templates',
                __DIR__.'/../../faq-bundle/src/Resources/contao/templates',
                __DIR__.'/../../listing-bundle/src/Resources/contao/templates',
                __DIR__.'/../../news-bundle/src/Resources/contao/templates',
                __DIR__.'/../../newsletter-bundle/src/Resources/contao/templates',
            ])
        ;
    }

    private function adjustSymfonyRules(array &$rules): void
    {
        $rules['blank_line_after_opening_tag'] = false;
        $rules['semicolon_after_instruction'] = false;
    }

    private function adjustPhpCsFixerRules(array &$rules): void
    {
        $rules['no_alternative_syntax'] = false;
        $rules['no_short_echo_tag'] = false;
        $rules['strict_comparison'] = false;
        $rules['strict_param'] = false;
    }

    private function adjustPhp71MigrationRules(array &$rules): void
    {
        $rules['declare_strict_types'] = false;
        $rules['visibility_required'] = false;
        $rules['void_return'] = false;
    }

    private function adjustOtherRules(array &$rules): void
    {
        $rules['linebreak_after_opening_tag'] = false;
    }
}
