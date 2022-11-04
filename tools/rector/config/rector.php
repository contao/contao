<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\Php74\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->parallel();

    $rectorConfig->import(SetList::PHP_80);
    $rectorConfig->import(SetList::PHP_81);

    $rectorConfig->paths([
        __DIR__ . '/../../../*-bundle/bin',
        __DIR__ . '/../../../*-bundle/src',
        __DIR__ . '/../../../*-bundle/tests',
        __DIR__ . '/../../../test-case/src',
    ]);

    $rectorConfig->skip([
        '*/Fixtures/system/*',
        '*-bundle/contao/*',
        ClassPropertyAssignToConstructorPromotionRector::class => [
            '*/src/Entity/*',
            'core-bundle/src/Image/Preview/PreviewFactory.php',
            'manager-bundle/src/Command/ContaoSetupCommand.php',
        ],
        ChangeSwitchToMatchRector::class,
        ReadOnlyPropertyRector::class,
    ]);

    $services = $rectorConfig->services();
    $services->set(ArraySpreadInsteadOfArrayMergeRector::class);
    $services->set(CompactToVariablesRector::class);
    $services->set(RemoveUnusedPrivateMethodParameterRector::class);
    $services->set(RestoreDefaultNullToNullableTypePropertyRector::class);
    $services->set(TypedPropertyRector::class);
};
