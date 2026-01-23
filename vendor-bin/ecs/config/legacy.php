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
use Contao\EasyCodingStandard\Fixer\MultiLineLambdaFunctionArgumentsFixer;
use Contao\EasyCodingStandard\Set\SetList;
use Contao\EasyCodingStandard\Sniffs\UseSprintfInExceptionsSniff;
use PhpCsFixer\Fixer\Alias\ModernizeStrposFixer;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Basic\BracesPositionFixer;
use PhpCsFixer\Fixer\Basic\PsrAutoloadingFixer;
use PhpCsFixer\Fixer\ClassNotation\ModifierKeywordsFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\ControlStructureContinuationPositionFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\LanguageConstruct\GetClassToClassKeywordFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use PhpCsFixer\Fixer\Whitespace\StatementIndentationFixer;
use SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowArrayTypeHintSyntaxSniff;
use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use SlevomatCodingStandard\Sniffs\Variables\UselessVariableSniff;
use SlevomatCodingStandard\Sniffs\Whitespaces\DuplicateSpacesSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/../../../calendar-bundle/contao',
        __DIR__.'/../../../comments-bundle/contao',
        __DIR__.'/../../../core-bundle/contao',
        __DIR__.'/../../../faq-bundle/contao',
        __DIR__.'/../../../listing-bundle/contao',
        __DIR__.'/../../../manager-bundle/contao',
        __DIR__.'/../../../news-bundle/contao',
        __DIR__.'/../../../newsletter-bundle/contao',
    ])
    ->withSkip([
        '*/languages/*',
        '*/templates/*',
        '*/themes/*',
        BinaryOperatorSpacesFixer::class,
        ChainedMethodBlockFixer::class,
        CommentLengthFixer::class,
        DeclareStrictTypesFixer::class,
        DisallowArrayTypeHintSyntaxSniff::class,
        DuplicateSpacesSniff::class,
        GetClassToClassKeywordFixer::class,
        IncrementStyleFixer::class,
        MethodChainingIndentationFixer::class,
        ModernizeStrposFixer::class,
        ModifierKeywordsFixer::class,
        MultiLineLambdaFunctionArgumentsFixer::class,
        MultilineWhitespaceBeforeSemicolonsFixer::class,
        NoSpacesAfterFunctionNameFixer::class,
        NoSuperfluousPhpdocTagsFixer::class,
        OrderedClassElementsFixer::class,
        PhpdocOrderFixer::class,
        PhpdocScalarFixer::class,
        PhpdocSeparationFixer::class,
        PhpdocSummaryFixer::class,
        PsrAutoloadingFixer::class,
        ReturnAssignmentFixer::class,
        SingleQuoteFixer::class,
        StatementIndentationFixer::class => [
            'core-bundle/contao/library/Contao/Config.php',
            'core-bundle/contao/library/Contao/Image.php',
            'core-bundle/contao/library/Contao/Template.php',
        ],
        StrictComparisonFixer::class,
        StrictParamFixer::class,
        TrailingCommaInMultilineFixer::class,
        UnusedVariableSniff::class,
        UseArrowFunctionsFixer::class,
        UselessParenthesesSniff::class,
        UselessVariableSniff::class,
        UseSprintfInExceptionsSniff::class,
        VoidReturnFixer::class,
        YodaStyleFixer::class,
    ])
    ->withParallel()
    ->withSpacing(Option::INDENTATION_TAB, "\n")
    ->withConfiguredRule(ArraySyntaxFixer::class, ['syntax' => 'long'])
    ->withConfiguredRule(BracesPositionFixer::class, ['control_structures_opening_brace' => BracesPositionFixer::NEXT_LINE_UNLESS_NEWLINE_AT_SIGNATURE_END])
    ->withConfiguredRule(ConcatSpaceFixer::class, ['spacing' => 'one'])
    ->withConfiguredRule(ControlStructureContinuationPositionFixer::class, ['position' => ControlStructureContinuationPositionFixer::NEXT_LINE])
    ->withConfiguredRule(HeaderCommentFixer::class, ['header' => "This file is part of Contao.\n\n(c) Leo Feyer\n\n@license LGPL-3.0-or-later"])
    ->withConfiguredRule(ListSyntaxFixer::class, ['syntax' => 'long'])
    ->withConfiguredRule(NoExtraBlankLinesFixer::class, ['tokens' => ['curly_brace_block', 'extra', 'parenthesis_brace_block', 'square_brace_block', 'use']])
    ->withCache(sys_get_temp_dir().'/ecs/contao57-legacy')
;
