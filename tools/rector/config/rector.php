<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\FuncCall\CountArrayToEmptyArrayComparisonRector;
use Rector\Core\Configuration\Option;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php73\Rector\FuncCall\ArrayKeyFirstLastRector;
use Rector\Php74\Rector\FuncCall\ArraySpreadInsteadOfArrayMergeRector;
use Rector\Php74\Rector\Property\RestoreDefaultNullToNullableTypePropertyRector;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Php80\Rector\Identical\StrEndsWithRector;
use Rector\Php80\Rector\Identical\StrStartsWithRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
use Rector\Php81\Rector\FuncCall\NullToStrictStringFuncCallArgRector;
use Rector\Php81\Rector\FunctionLike\IntersectionTypesRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
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
    ]);

    $services = $containerConfigurator->services();
    $services->set(ArrayKeyFirstLastRector::class);
    $services->set(ArraySpreadInsteadOfArrayMergeRector::class);
    $services->set(ClassConstantToSelfClassRector::class);
    $services->set(ClassOnObjectRector::class);
    $services->set(CountArrayToEmptyArrayComparisonRector::class);
    $services->set(FinalizePublicClassConstantRector::class);
    $services->set(IntersectionTypesRector::class);
    $services->set(NullToStrictStringFuncCallArgRector::class);
    $services->set(RemoveUnusedVariableInCatchRector::class);
    $services->set(RestoreDefaultNullToNullableTypePropertyRector::class);
    $services->set(ReturnNeverTypeRector::class);
    $services->set(StringableForToStringRector::class);
    $services->set(StrContainsRector::class);
    $services->set(StrEndsWithRector::class);
    $services->set(StrStartsWithRector::class);
    $services->set(TypedPropertyRector::class);
    $services->set(UnionTypesRector::class);
};
