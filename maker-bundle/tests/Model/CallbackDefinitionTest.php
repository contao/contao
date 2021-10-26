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

use Contao\MakerBundle\Model\CallbackDefinition;
use Contao\MakerBundle\Model\MethodDefinition;
use PHPUnit\Framework\TestCase;

class CallbackDefinitionTest extends TestCase
{
    public function testReturnsDependencies(): void
    {
        $dependencies = [];
        $methodDefinition = new MethodDefinition(null, []);

        $callbackDefinition = new CallbackDefinition($methodDefinition, $dependencies);

        $this->assertSame([], $callbackDefinition->getDependencies());
        $this->assertSame($methodDefinition, $callbackDefinition->getMethodDefinition());
    }
}
