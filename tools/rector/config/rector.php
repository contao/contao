<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Php80\Rector\Catch_\RemoveUnusedVariableInCatchRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Php80\Rector\Identical\StrEndsWithRector;
use Rector\Php80\Rector\Identical\StrStartsWithRector;
use Rector\Php80\Rector\NotIdentical\StrContainsRector;
use Rector\Php81\Rector\ClassConst\FinalizePublicClassConstantRector;
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
    $services->set(FinalizePublicClassConstantRector::class);
    $services->set(IntersectionTypesRector::class);
    $services->set(RemoveUnusedVariableInCatchRector::class);
    $services->set(ReturnNeverTypeRector::class);
    $services->set(StrContainsRector::class);
    $services->set(StrEndsWithRector::class);
    $services->set(StrStartsWithRector::class);
    $services->set(TypedPropertyRector::class);
    $services->set(UnionTypesRector::class);
};
