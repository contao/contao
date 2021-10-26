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

class CallbackDefinition
{
    private MethodDefinition $methodDefinition;

    /**
     * @var array<int, string>
     */
    private array $dependencies;

    /**
     * @param array<int, string> $dependencies
     */
    public function __construct(MethodDefinition $methodDefinition, array $dependencies = [])
    {
        $this->methodDefinition = $methodDefinition;
        $this->dependencies = $dependencies;
    }

    /**
     * @return array<int, string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    public function getMethodDefinition(): MethodDefinition
    {
        return $this->methodDefinition;
    }
}
