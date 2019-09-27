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

class LegacyConfig extends DefaultConfig
{
    public function getName(): string
    {
        return 'Contao legacy';
    }

    public function getIndent(): string
    {
        return "\t";
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        $this->adjustPsr2Rules($rules);
        $this->adjustSymfonyRules($rules);
        $this->adjustPhpCsFixerRules($rules);
        $this->adjustPhp71MigrationRules($rules);
        $this->adjustOtherRules($rules);

        return $rules;
    }

    public function getFinder(): Finder
    {
        return (new Finder())
            ->exclude('languages')
            ->exclude('templates')
            ->exclude('themes')
            ->in([
                __DIR__.'/../../calendar-bundle/src/Resources/contao',
                __DIR__.'/../../comments-bundle/src/Resources/contao',
                __DIR__.'/../../core-bundle/src/Resources/contao',
                __DIR__.'/../../faq-bundle/src/Resources/contao',
                __DIR__.'/../../listing-bundle/src/Resources/contao',
                __DIR__.'/../../news-bundle/src/Resources/contao',
                __DIR__.'/../../newsletter-bundle/src/Resources/contao',
            ])
        ;
    }

    private function adjustPsr2Rules(array &$rules): void
    {
        $rules['braces'] = [
            'allow_single_line_closure' => true,
            'position_after_anonymous_constructs' => 'next',
            'position_after_control_structures' => 'next',
        ];

        $rules['no_spaces_after_function_name'] = false;
    }

    private function adjustSymfonyRules(array &$rules): void
    {
        $rules['binary_operator_spaces'] = false;
        $rules['concat_space'] = ['spacing' => 'one'];
        $rules['increment_style'] = false;
        $rules['phpdoc_scalar'] = false;
        $rules['phpdoc_separation'] = false;
        $rules['phpdoc_summary'] = false;
        $rules['phpdoc_to_comment'] = false;
        $rules['return_assignment'] = false;
        $rules['single_quote'] = false;
        $rules['trailing_comma_in_multiline_array'] = false;
        $rules['yoda_style'] = false;

        if (false !== $key = array_search('throw', $rules['no_extra_blank_lines']['tokens'], true)) {
            unset($rules['no_extra_blank_lines']['tokens'][$key]);
        }
    }

    private function adjustPhpCsFixerRules(array &$rules): void
    {
        $rules['array_syntax'] = ['syntax' => 'long'];
        $rules['combine_consecutive_issets'] = false;
        $rules['combine_consecutive_unsets'] = false;
        $rules['multiline_whitespace_before_semicolons'] = false;
        $rules['ordered_class_elements'] = false;
        $rules['phpdoc_order'] = false;
        $rules['strict_comparison'] = false;
        $rules['strict_param'] = false;

        if (false !== $key = array_search('case', $rules['blank_line_before_statement']['statements'], true)) {
            unset($rules['blank_line_before_statement']['statements'][$key]);
        }
    }

    private function adjustPhp71MigrationRules(array &$rules): void
    {
        $rules['declare_strict_types'] = false;
        $rules['visibility_required'] = false;
        $rules['void_return'] = false;
    }

    private function adjustOtherRules(array &$rules): void
    {
        $rules['list_syntax'] = ['syntax' => 'long'];
        $rules['no_superfluous_phpdoc_tags'] = false;
    }
}
