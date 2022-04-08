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

class MethodDefinition
{
    /**
     * @param array<string, (string|array|null)> $parameters
     */
    public function __construct(private string|null $returnType, private array $parameters)
    {
    }

    public function getReturnType(): string|null
    {
        return $this->returnType;
    }

    /**
     * @return array<string, (string|array|null)>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getBody(): string
    {
        switch ($this->returnType) {
            case 'string':
                return "return '';";

            case '?string':
                return 'return null;';

            case 'array':
                return 'return [];';

            case 'bool':
                return 'return true;';

            default:
                return '// Do something';
        }
    }
}
