<?php

use Contao\CoreBundle\Twig\Defer\DeferTokenParser;
use Contao\CoreBundle\Twig\ResponseContext\AddTokenParser;
use Contao\CoreBundle\Twig\Slots\SlotTokenParser;
use TwigCsFixer\Config\Config;
use TwigCsFixer\File\Finder;
use TwigCsFixer\Rules\File\DirectoryNameRule;
use TwigCsFixer\Rules\File\FileExtensionRule;
use TwigCsFixer\Rules\File\FileNameRule;
use TwigCsFixer\Rules\Literal\CompactHashRule;
use TwigCsFixer\Rules\Node\ForbiddenFunctionRule;
use TwigCsFixer\Rules\Node\ValidConstantFunctionRule;
use TwigCsFixer\Rules\Variable\VariableNameRule;
use TwigCsFixer\Ruleset\Ruleset;
use TwigCsFixer\Standard\TwigCsFixer;

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
$ruleset->addStandard(new TwigCsFixer());

$ruleset->overrideRule(new CompactHashRule(true));
$ruleset->overrideRule(new VariableNameRule(optionalPrefix: '_'));

foreach ($templatePaths as $templatePath) {
    $ruleset->addRule(new DirectoryNameRule(baseDirectory: $templatePath));
    $ruleset->addRule(new FileNameRule(baseDirectory: $templatePath, optionalPrefix: '_'));
}

$ruleset->addRule(new FileExtensionRule());
$ruleset->addRule(new ValidConstantFunctionRule());

$ruleset->addRule(new ForbiddenFunctionRule([
    'contao_figure', // you should use the "figure" function instead
    'insert_tag', // you should not misuse insert tags in templates
    'contao_section', // only for legacy layouts
    'contao_sections', // only for legacy layouts
]));

$config = new Config();
$config->allowNonFixableRules();
$config->addTokenParser(new DeferTokenParser());
$config->addTokenParser(new AddTokenParser(''));
$config->addTokenParser(new SlotTokenParser());
$config->setRuleset($ruleset);
$config->setFinder((new Finder())->in(__DIR__ . '/*-bundle/contao/templates/twig/*'));
$config->setCacheFile(sys_get_temp_dir().'/twig-cs-fixer/contao5x');

return $config;
