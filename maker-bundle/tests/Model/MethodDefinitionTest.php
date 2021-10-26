<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Tests\Model;

use Contao\MakerBundle\Model\MethodDefinition;
use PHPUnit\Framework\TestCase;

class MethodDefinitionTest extends TestCase
{
    public function testCreationWithReturnValue(): void
    {
        $returnType = 'string';
        $parameters = [
            'name' => 'type',
        ];

        $hookDefinition = new MethodDefinition($returnType, $parameters);

        $this->assertSame($returnType, $hookDefinition->getReturnType());
        $this->assertSame($parameters, $hookDefinition->getParameters());
    }

    public function testCreationWithoutReturnValue(): void
    {
        $returnType = null;
        $parameters = [];

        $hookDefinition = new MethodDefinition($returnType, $parameters);

        $this->assertNull($hookDefinition->getReturnType());
        $this->assertSame($parameters, $hookDefinition->getParameters());
    }
}
