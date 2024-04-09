<?php

declare(strict_types=1);

namespace Contao\Tools\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use Spatie\SchemaOrg\Graph;

class GraphReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Graph::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'get' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type|null
    {
        $args = $methodCall->getArgs();

        if ([] === $args) {
            return null;
        }

        return $scope->getType($methodCall->getArgs()[0]->value)->getClassStringObjectType();
    }
}
