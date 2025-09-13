<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Contao\Rector\Set\SetList;
use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\Config\RectorConfig;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;

return RectorConfig::configure()
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
        ClassPropertyAssignToConstructorPromotionRector::class => [
            '*/src/Entity/*',
        ],
        StringClassNameToClassConstantRector::class => [
            'core-bundle/tests/PhpunitExtension/GlobalStateWatcher.php',
        ],
        FirstClassCallableRector::class => [
            'core-bundle/tests/Contao/InsertTagsTest.php',
            'core-bundle/tests/Twig/Interop/ContaoEscaperNodeVisitorTest.php',
            'core-bundle/tests/Twig/Interop/ContaoEscaperTest.php',
        ],
        NullToStrictStringFuncCallArgRector::class,
        SimplifyIfReturnBoolRector::class => [
            'core-bundle/src/EventListener/CommandSchedulerListener.php',
        ],
    ])
    ->withRootFiles()
    ->withParallel()
    ->withCache(sys_get_temp_dir().'/rector/contao5x')
;
