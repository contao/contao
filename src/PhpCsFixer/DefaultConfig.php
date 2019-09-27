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

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

class DefaultConfig extends Config
{
    /**
     * @var string
     */
    protected $header;

    public function __construct(string $header = null)
    {
        parent::__construct();

        $this->header = $header;
    }

    public function getName(): string
    {
        return 'Contao default';
    }

    public function getRules(): array
    {
        $rules = [
            '@PhpCsFixer' => true,
            '@PhpCsFixer:risky' => true,
            '@PHPUnit57Migration:risky' => true,

            // @PhpCsFixer adjustments
            'blank_line_before_statement' => [
                'statements' => [
                    'case',
                    'declare',
                    'do',
                    'for',
                    'foreach',
                    'if',
                    'return',
                    'switch',
                    'try',
                    'while',
                ],
            ],
            'explicit_indirect_variable' => false,
            'explicit_string_variable' => false,
            'method_chaining_indentation' => false,
            'no_extra_blank_lines' => [
                'tokens' => [
                    'curly_brace_block',
                    'extra',
                    'parenthesis_brace_block',
                    'square_brace_block',
                    'throw',
                    'use',
                ],
            ],
            'php_unit_internal_class' => false,
            'php_unit_test_class_requires_covers' => false,
            'phpdoc_types_order' => false,
            'single_line_comment_style' => [
                'comment_types' => ['hash'],
            ],

            // @PhpCsFixer:risky adjustments
            'final_internal_class' => false,
            'php_unit_test_case_static_method_calls' => [
                'call_type' => 'this',
            ],

            // Other
            'linebreak_after_opening_tag' => true,
        ];

        if (null !== $this->header) {
            $rules['header_comment'] = ['header' => $this->header];
        }

        return $rules;
    }

    public function getFinder(): Finder
    {
        return (new Finder())
            ->exclude('Resources')
            ->exclude('Fixtures')
            ->notPath('var/cache')
            ->in([
                __DIR__.'/../../calendar-bundle/src',
                __DIR__.'/../../calendar-bundle/tests',
                __DIR__.'/../../comments-bundle/src',
                __DIR__.'/../../core-bundle/src',
                __DIR__.'/../../core-bundle/tests',
                __DIR__.'/../../faq-bundle/src',
                __DIR__.'/../../faq-bundle/tests',
                __DIR__.'/../../installation-bundle/src',
                __DIR__.'/../../installation-bundle/tests',
                __DIR__.'/../../listing-bundle/src',
                __DIR__.'/../../manager-bundle/src',
                __DIR__.'/../../manager-bundle/tests',
                __DIR__.'/../../news-bundle/src',
                __DIR__.'/../../news-bundle/tests',
                __DIR__.'/../../newsletter-bundle/src',
            ])
        ;
    }

    public function getRiskyAllowed(): bool
    {
        return true;
    }

    public function getCacheFile(): string
    {
        return sys_get_temp_dir().'/'.strtr(static::class, '\\', '_');
    }
}
