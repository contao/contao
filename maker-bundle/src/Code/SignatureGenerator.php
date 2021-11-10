<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Code;

use Contao\MakerBundle\Model\MethodDefinition;
use Symfony\Bundle\MakerBundle\Str;

class SignatureGenerator
{
    public function generate(MethodDefinition $method, string $methodName): string
    {
        $returnType = $method->getReturnType();

        if (null !== $returnType && class_exists($returnType, true)) {
            $returnType = Str::getShortClassName($returnType);
        }

        $returnType = $returnType ? ': '.$returnType : '';
        $parameterTemplates = [];

        foreach ($method->getParameters() as $name => $type) {
            $defaultValue = null;

            if (\is_array($type)) {
                [$type, $defaultValue] = $type;
            }

            $paramName = str_replace('&', '', $name);
            [$paramType] = \is_array($type) ? $type : [$type, null];

            if (null !== $paramType && class_exists($paramType, true)) {
                $paramType = Str::getShortClassName($paramType);
            }

            $paramReference = 0 === strpos($name, '&');
            $parameterTemplate = sprintf('%s %s$%s', $paramType, $paramReference ? '&' : '', $paramName);

            if (null !== $defaultValue) {
                $parameterTemplate = sprintf('%s = %s', $parameterTemplate, $defaultValue);
            }

            $parameterTemplate = trim($parameterTemplate);
            $parameterTemplates[] = $parameterTemplate;
        }

        return sprintf('public function %s(%s)%s', $methodName, implode(', ', $parameterTemplates), $returnType);
    }
}
