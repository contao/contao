<?php

declare(strict_types=1);

use Contao\EasyCodingStandard\Fixer\MultiLineLambdaFunctionArgumentsFixer;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Basic\BracesFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\NoSpacesAfterFunctionNameFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use PhpCsFixer\Fixer\Operator\IncrementStyleFixer;
use PhpCsFixer\Fixer\Phpdoc\NoSuperfluousPhpdocTagsFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocOrderFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocScalarFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSeparationFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocSummaryFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocToCommentFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\Semicolon\MultilineWhitespaceBeforeSemicolonsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;
use PhpCsFixer\Fixer\Strict\StrictComparisonFixer;
use PhpCsFixer\Fixer\Strict\StrictParamFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use SlevomatCodingStandard\Sniffs\PHP\UselessParenthesesSniff;
use SlevomatCodingStandard\Sniffs\TypeHints\DisallowArrayTypeHintSyntaxSniff;
use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use SlevomatCodingStandard\Sniffs\Variables\UselessVariableSniff;
use SlevomatCodingStandard\Sniffs\Whitespaces\DuplicateSpacesSniff;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(__DIR__.'/../vendor/contao/easy-coding-standard/config/contao.php');

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PARALLEL, true);

    $parameters->set(Option::SKIP, [
        '*/languages/*',
        '*/templates/*',
        '*/themes/*',
        BinaryOperatorSpacesFixer::class => null,
        DeclareStrictTypesFixer::class => null,
        DisallowArrayTypeHintSyntaxSniff::class => null,
        DuplicateSpacesSniff::class => null,
        IncrementStyleFixer::class => null,
        MethodChainingIndentationFixer::class => null,
        MultiLineLambdaFunctionArgumentsFixer::class => null,
        MultilineWhitespaceBeforeSemicolonsFixer::class => null,
        NoSpacesAfterFunctionNameFixer::class => null,
        NoSuperfluousPhpdocTagsFixer::class => null,
        OrderedClassElementsFixer::class => null,
        PhpdocOrderFixer::class => null,
        PhpdocScalarFixer::class => null,
        PhpdocSeparationFixer::class => null,
        PhpdocSummaryFixer::class => null,
        PhpdocToCommentFixer::class => null,
        ReturnAssignmentFixer::class => null,
        SingleQuoteFixer::class => null,
        StrictComparisonFixer::class => null,
        StrictParamFixer::class => null,
        TrailingCommaInMultilineFixer::class => null,
        UnusedVariableSniff::class => null,
        UseArrowFunctionsFixer::class => null,
        UselessParenthesesSniff::class => null,
        UselessVariableSniff::class => null,
        VisibilityRequiredFixer::class => null,
        VoidReturnFixer::class => null,
        YodaStyleFixer::class => null,
    ]);

    $parameters->set(Option::INDENTATION, 'tab');
    $parameters->set(Option::LINE_ENDING, "\n");
    $parameters->set(Option::CACHE_DIRECTORY, sys_get_temp_dir().'/ecs_legacy_cache');

    $services = $containerConfigurator->services();
    $services
        ->set(ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'long',
        ]])
    ;

    $services
        ->set(BlankLineBeforeStatementFixer::class)
        ->call('configure', [[
            // Remove "case"
            'statements' => ['declare', 'default', 'do', 'for', 'foreach', 'if', 'return', 'switch', 'throw', 'try', 'while'],
        ]])
    ;

    $services
        ->set(BracesFixer::class)
        ->call('configure', [[
            'allow_single_line_closure' => true,
            'position_after_anonymous_constructs' => BracesFixer::LINE_NEXT,
            'position_after_control_structures' => BracesFixer::LINE_NEXT,
        ]])
    ;

    $services
        ->set(ConcatSpaceFixer::class)
        ->call('configure', [[
            'spacing' => 'one',
        ]])
    ;

    $services
        ->set(HeaderCommentFixer::class)
        ->call('configure', [[
            'header' => "This file is part of Contao.\n\n(c) Leo Feyer\n\n@license LGPL-3.0-or-later",
        ]])
    ;

    $services
        ->set(ListSyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'long',
        ]])
    ;

    $services
        ->set(NoExtraBlankLinesFixer::class)
        ->call('configure', [[
            // Remove "throw"
            'tokens' => ['curly_brace_block', 'extra', 'parenthesis_brace_block', 'square_brace_block', 'use'],
        ]])
    ;
};
