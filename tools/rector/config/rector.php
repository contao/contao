<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php74\Rector\Property\TypedPropertyRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
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
    $services->set(IntersectionTypesRector::class);
    $services->set(ReturnNeverTypeRector::class);
    $services->set(TypedPropertyRector::class);
    $services->set(UnionTypesRector::class);
};
