<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Rector\CodeQuality\Rector\If_\SimplifyIfReturnBoolRector;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;

return RectorConfig::configure()
    ->withSets([__DIR__.'/../vendor/contao/rector/config/contao.php'])
    ->withPaths([
        __DIR__.'/../../../*/bin',
        __DIR__.'/../../../*/src',
        __DIR__.'/../../../*/tests',
        __DIR__.'/../../../vendor-bin/*/bin',
        __DIR__.'/../../../vendor-bin/*/config',
        __DIR__.'/../../../vendor-bin/*/src',
    ])
    ->withSkip([
        ClassPropertyAssignToConstructorPromotionRector::class => [
            '*/src/Entity/*',
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
    ->withParallel()
    ->withCache(sys_get_temp_dir().'/rector_cache')
;
