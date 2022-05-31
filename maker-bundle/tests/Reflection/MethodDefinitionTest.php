<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\MakerBundle\Tests\Reflection;

use Contao\MakerBundle\Reflection\MethodDefinition;
use PHPUnit\Framework\TestCase;

class MethodDefinitionTest extends TestCase
{
    /**
     * @dataProvider getReturnValues
     */
    public function testSetsTheCorrectMethodBody(string|null $returnType, string $body): void
    {
        $hookDefinition = new MethodDefinition($returnType, []);

        $this->assertSame($returnType, $hookDefinition->getReturnType());
        $this->assertSame([], $hookDefinition->getParameters());
        $this->assertSame($body, $hookDefinition->getBody());
    }

    public function getReturnValues(): \Generator
    {
        yield ['string', "return '';"];
        yield ['?string', 'return null;'];
        yield ['array', 'return [];'];
        yield ['bool', 'return true;'];
        yield [null, '// Do something'];
        yield ['Foo\Bar\Class', '// Do something'];
    }
}
