<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\EasyCodingStandard\Set\SetList;
use PhpCsFixer\Fixer\Comment\HeaderCommentFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use SlevomatCodingStandard\Sniffs\Variables\UnusedVariableSniff;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    ->withSets([SetList::CONTAO])
    ->withPaths([
        __DIR__.'/calendar-bundle/src',
        __DIR__.'/calendar-bundle/tests',
        __DIR__.'/comments-bundle/src',
        __DIR__.'/comments-bundle/tests',
        __DIR__.'/core-bundle/src',
        __DIR__.'/core-bundle/tests',
        __DIR__.'/faq-bundle/src',
        __DIR__.'/faq-bundle/tests',
        __DIR__.'/listing-bundle/src',
        __DIR__.'/maker-bundle/src',
        __DIR__.'/maker-bundle/tests',
        __DIR__.'/manager-bundle/bin',
        __DIR__.'/manager-bundle/src',
        __DIR__.'/manager-bundle/tests',
        __DIR__.'/news-bundle/src',
        __DIR__.'/news-bundle/tests',
        __DIR__.'/newsletter-bundle/src',
        __DIR__.'/newsletter-bundle/tests',
        __DIR__.'/test-case/src',
        __DIR__.'/vendor-bin/ecs/config',
        __DIR__.'/vendor-bin/phpstan/src',
        __DIR__.'/vendor-bin/service-linter/src',
    ])
    ->withSkip([
        MethodChainingIndentationFixer::class => [
            '*/DependencyInjection/Configuration.php',
        ],
        UnusedVariableSniff::class => [
            'core-bundle/tests/Session/Attribute/ArrayAttributeBagTest.php',
        ],
    ])
    ->withRootFiles()
    ->withParallel()
    ->withSpacing(Option::INDENTATION_SPACES, "\n")
    ->withConfiguredRule(HeaderCommentFixer::class, ['header' => "This file is part of Contao.\n\n(c) Leo Feyer\n\n@license LGPL-3.0-or-later"])
    ->withCache(sys_get_temp_dir().'/ecs/contao56')
;
