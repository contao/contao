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

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([__DIR__.'/../vendor/contao/rector/config/contao.php']);

    $rectorConfig->paths([
        __DIR__.'/../../../*/bin',
        __DIR__.'/../../../*/src',
        __DIR__.'/../../../*/tests',
        __DIR__.'/../../../tools/*/bin',
        __DIR__.'/../../../tools/*/config',
        __DIR__.'/../../../tools/*/src',
    ]);

    $rectorConfig->skip([
        '*-bundle/contao/*',
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
            'core-bundle/src/HttpKernel/ModelArgumentResolver.php',
        ],
    ]);

    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(sys_get_temp_dir().'/rector_cache');
};
