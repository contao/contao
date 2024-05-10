<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\EasyCodingStandard\Fixer\ChainedMethodBlockFixer;
use Contao\EasyCodingStandard\Fixer\CommentLengthFixer;
use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\ControlStructure\NoAlternativeSyntaxFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\PhpTag\BlankLineAfterOpeningTagFixer;
use PhpCsFixer\Fixer\PhpTag\LinebreakAfterOpeningTagFixer;
use PhpCsFixer\Fixer\Semicolon\SemicolonAfterInstructionFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;
use SlevomatCodingStandard\Sniffs\Namespaces\ReferenceUsedNamesOnlySniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/../../../calendar-bundle/contao/templates',
        __DIR__.'/../../../comments-bundle/contao/templates',
        __DIR__.'/../../../core-bundle/contao/templates',
        __DIR__.'/../../../faq-bundle/contao/templates',
        __DIR__.'/../../../listing-bundle/contao/templates',
        __DIR__.'/../../../news-bundle/contao/templates',
        __DIR__.'/../../../newsletter-bundle/contao/templates',
    ])
    ->withSkip([
        BlankLineAfterOpeningTagFixer::class,
        ChainedMethodBlockFixer::class,
        CommentLengthFixer::class,
        DeclareStrictTypesFixer::class,
        LinebreakAfterOpeningTagFixer::class,
        NoAlternativeSyntaxFixer::class,
        ReferenceUsedNamesOnlySniff::class,
        SemicolonAfterInstructionFixer::class,
        StatementIndentationFixer::class,
        StrictComparisonFixer::class,
        StrictParamFixer::class,
        VisibilityRequiredFixer::class,
        VoidReturnFixer::class,
    ])
    ->withParallel()
    ->withSpacing(Option::INDENTATION_SPACES, "\n")
    ->withFileExtensions(['html5'])
    ->withCache(sys_get_temp_dir().'/ecs/contao5x-template')
;
