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
    public function __construct(
        private readonly string|null $returnType,
        private readonly array $parameters,
    ) {
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
        return match ($this->returnType) {
            'string' => "return '';",
            '?string' => 'return null;',
            'array' => 'return [];',
            'bool' => 'return true;',
            default => '// Do something',
        };
    }
}
