<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Reflection;

class ImportExtractor
{
    /**
     * @return array<int, string>
     */
    public function extract(MethodDefinition $method): array
    {
        $objectTypeHints = [];

        foreach ($method->getParameters() as $parameter) {
            if (null === $parameter) {
                continue;
            }

            $type = \is_array($parameter) ? $parameter[0] : $parameter;

            if (!class_exists((string) $type)) {
                continue;
            }

            $objectTypeHints[] = $type;
        }

        $returnType = $method->getReturnType();

        // If a return type is set, check if the class exists and add it to our imports
        if (null !== $returnType && class_exists($returnType)) {
            $objectTypeHints[] = $returnType;
        }

        $objectTypeHints = array_unique($objectTypeHints);
        sort($objectTypeHints);

        return $objectTypeHints;
    }
}
