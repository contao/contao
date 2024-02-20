<?php

declare(strict_types=1);

namespace Contao\Tools\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;

class HelperReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Command::class;
    }

    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return 'getHelper' === $methodReflection->getName();
    }

    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type|null
    {
        $args = $methodCall->getArgs();

        if ([] === $args) {
            return null;
        }

        $arg = $methodCall->getArgs()[0]->value;

        if ('question' === $arg->value) {
            return new ObjectType(QuestionHelper::class);
        }

        return $scope->getType($arg);
    }
}
