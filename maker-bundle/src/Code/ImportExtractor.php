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

            if (!class_exists((string) $type, true)) {
                continue;
            }

            $objectTypeHints[] = $type;
        }

        $returnType = $method->getReturnType();

        // If a return type is set, check if class exists
        // and if so, add it to our imports
        if (null !== $returnType) {
            if (class_exists($returnType, true)) {
                $objectTypeHints[] = $returnType;
            }
        }

        return array_unique($objectTypeHints);
    }
}
