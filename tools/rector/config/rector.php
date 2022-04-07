<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\CompactToVariablesRector;
use Rector\Core\Configuration\Option;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\Php74\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->import(SetList::PHP_80);
    $containerConfigurator->import(SetList::PHP_81);

    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PARALLEL, true);

    $parameters->set(Option::PATHS, [
        __DIR__ . '/../../../*-bundle/bin',
        __DIR__ . '/../../../*-bundle/src',
        __DIR__ . '/../../../*-bundle/tests',
        __DIR__ . '/../../../test-case/src',
    ]);

    $parameters->set(Option::SKIP, [
        '*/Fixtures/system/*',
        '*/Resources/contao/*',
        ClassPropertyAssignToConstructorPromotionRector::class => [
            '*/src/Entity/*',
        ],
        ChangeSwitchToMatchRector::class,
        ReadOnlyPropertyRector::class => [
            'core-bundle/src/DependencyInjection/Filesystem/FilesystemConfiguration.php',
            'core-bundle/src/Security/Exception/LockedException.php',
            'core-bundle/src/Security/Authentication/Token/FrontendPreviewToken.php',
        ],
    ]);

    $services = $containerConfigurator->services();
    $services->set(ArraySpreadInsteadOfArrayMergeRector::class);
    $services->set(CompactToVariablesRector::class);
    $services->set(RemoveUnusedPrivateMethodParameterRector::class);
    $services->set(RestoreDefaultNullToNullableTypePropertyRector::class);
    $services->set(TypedPropertyRector::class);
};
