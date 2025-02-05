<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\Tools\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ExtensionReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return ContainerBuilder::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'getExtension' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type|null
    {
        $args = $methodCall->getArgs();

        if ([] === $args) {
            return null;
        }

        $arg = $methodCall->getArgs()[0]->value;

        if ('security' === $arg->value) {
            return new ObjectType(SecurityExtension::class);
        }

        return $scope->getType($arg);
    }
}
