<?php

use Contao\CoreBundle\Twig\Defer\DeferTokenParser;
use Contao\CoreBundle\Twig\ResponseContext\AddTokenParser;
use Contao\CoreBundle\Twig\Slots\SlotTokenParser;
use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\Delimiter\BlockNameSpacingRule;
use TwigCsFixer\Rules\Delimiter\DelimiterSpacingRule;
use TwigCsFixer\Rules\File\DirectoryNameRule;
use TwigCsFixer\Rules\File\FileExtensionRule;
use TwigCsFixer\Rules\File\FileNameRule;
use TwigCsFixer\Rules\Function\IncludeFunctionRule;
use TwigCsFixer\Rules\Function\MacroArgumentNameRule;
use TwigCsFixer\Rules\Function\NamedArgumentNameRule;
use TwigCsFixer\Rules\Function\NamedArgumentSeparatorRule;
use TwigCsFixer\Rules\Function\NamedArgumentSpacingRule;
use TwigCsFixer\Rules\Literal\CompactHashRule;
use TwigCsFixer\Rules\Literal\HashQuoteRule;
use TwigCsFixer\Rules\Literal\SingleQuoteRule;
use TwigCsFixer\Rules\Node\ForbiddenFunctionRule;
use TwigCsFixer\Rules\Node\ValidConstantFunctionRule;
use TwigCsFixer\Rules\Operator\OperatorNameSpacingRule;
use TwigCsFixer\Rules\Operator\OperatorSpacingRule;
use TwigCsFixer\Rules\Punctuation\PunctuationSpacingRule;
use TwigCsFixer\Rules\Punctuation\TrailingCommaMultiLineRule;
use TwigCsFixer\Rules\Punctuation\TrailingCommaSingleLineRule;
use TwigCsFixer\Rules\Variable\VariableNameRule;
use TwigCsFixer\Rules\Whitespace\BlankEOFRule;
use TwigCsFixer\Rules\Whitespace\EmptyLinesRule;
use TwigCsFixer\Rules\Whitespace\IndentRule;
use TwigCsFixer\Rules\Whitespace\TrailingSpaceRule;
use TwigCsFixer\Ruleset\Ruleset;

require_once __DIR__ . '/vendor-bin/twig-cs-fixer/vendor/autoload.php';

$templatePaths = [
    __DIR__ . '/calendar-bundle/contao/templates',
    __DIR__ . '/comments-bundle/contao/templates',
    __DIR__ . '/core-bundle/contao/templates',
    __DIR__ . '/faq-bundle/contao/templates',
    __DIR__ . '/listing-bundle/contao/templates',
    __DIR__ . '/news-bundle/contao/templates',
    __DIR__ . '/newsletter-bundle/contao/templates',
];

$ruleset = new Ruleset();

// Delimiter rules
$ruleset->addRule(new BlockNameSpacingRule());
$ruleset->addRule(new DelimiterSpacingRule());

// File rules
foreach ($templatePaths as $templatePath) {
    $ruleset->addRule(new DirectoryNameRule(baseDirectory: $templatePath));
    $ruleset->addRule(new FileNameRule(baseDirectory: $templatePath, optionalPrefix: '_'));
}

$ruleset->addRule(new FileExtensionRule());

// Function rules
$ruleset->addRule(new IncludeFunctionRule());
$ruleset->addRule(new MacroArgumentNameRule());
$ruleset->addRule(new NamedArgumentSeparatorRule());
$ruleset->addRule(new NamedArgumentNameRule());
$ruleset->addRule(new NamedArgumentSpacingRule());

// Literal rules
$ruleset->addRule(new CompactHashRule(true));
$ruleset->addRule(new HashQuoteRule());
$ruleset->addRule(new SingleQuoteRule());

// Node rules
$ruleset->addRule(new ValidConstantFunctionRule());
$ruleset->addRule(new ForbiddenFunctionRule([
    'contao_figure', // you should use the "figure" function instead
    'insert_tag', // you should not misuse insert tags in templates
    'contao_sections', // only for legacy layout
    'contao_section', // only for legacy layout
]));

// Operator rules
$ruleset->addRule(new PunctuationSpacingRule());
$ruleset->addRule(new OperatorNameSpacingRule());
$ruleset->addRule(new OperatorSpacingRule());

// Punctuation rules
$ruleset->addRule(new TrailingCommaMultiLineRule());
$ruleset->addRule(new TrailingCommaSingleLineRule());

// Variable rules
$ruleset->addRule(new VariableNameRule(optionalPrefix: '_'));

// Whitespace rules
$ruleset->addRule(new BlankEOFRule());
$ruleset->addRule(new EmptyLinesRule());
$ruleset->addRule(new IndentRule());
$ruleset->addRule(new TrailingSpaceRule());

$config = new Config();
$config->allowNonFixableRules();
$config->setRuleset($ruleset);

$config->addTokenParser(new DeferTokenParser());
$config->addTokenParser(new AddTokenParser(''));
$config->addTokenParser(new SlotTokenParser());

// Only lint/fix templates in subdirectories, otherwise we would also target our surrogate templates
$config->setFinder((new Finder())->in(__DIR__ . '/*-bundle/contao/templates/twig/*'));

return $config;
