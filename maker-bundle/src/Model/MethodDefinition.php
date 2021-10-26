<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Model;

class MethodDefinition
{
    private ?string $returnType;

    /**
     * @var array<string, (string|array|null)>
     */
    private array $parameters;

    /**
     * @param array<string, (string|array|null)> $parameters
     */
    public function __construct(?string $returnType, array $parameters)
    {
        $this->returnType = $returnType;
        $this->parameters = $parameters;
    }

    public function getReturnType(): ?string
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
}
