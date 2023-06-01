<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\Php74\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();

    $rectorConfig->import(SetList::PHP_80);
    $rectorConfig->import(SetList::PHP_81);

    $rectorConfig->paths([
        __DIR__.'/../../../*/bin',
        __DIR__.'/../../../*/src',
        __DIR__.'/../../../*/tests',
        __DIR__.'/../../../tools/*/bin',
        __DIR__.'/../../../tools/*/config',

        // Using ../tools/*/src leads to a "class was not found while trying to analyse
        // it" error, so add the paths to the /src directories explicitly.
        __DIR__.'/../../../tools/isolated-tests/src',
        __DIR__.'/../../../tools/servlice-linter/src',
    ]);

    $rectorConfig->skip([
        '*/Fixtures/system/*',
        '*-bundle/contao/*',
        '*-bundle/src/Resources/contao/*',
        ClassPropertyAssignToConstructorPromotionRector::class => [
            '*/src/Entity/*',
        ],
        FirstClassCallableRector::class => [
            'core-bundle/tests/Contao/InsertTagsTest.php',
            'core-bundle/tests/Twig/Interop/ContaoEscaperNodeVisitorTest.php',
            'core-bundle/tests/Twig/Interop/ContaoEscaperTest.php',
        ],
        NullToStrictStringFuncCallArgRector::class,
    ]);

    $services = $rectorConfig->services();
    $services->set(ArraySpreadInsteadOfArrayMergeRector::class);
    $services->set(CompactToVariablesRector::class);
    $services->set(RemoveUnusedPrivateMethodParameterRector::class);
    $services->set(RestoreDefaultNullToNullableTypePropertyRector::class);
};
