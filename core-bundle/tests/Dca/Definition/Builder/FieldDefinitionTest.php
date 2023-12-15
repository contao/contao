<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Dca\Definition\Builder;

use Contao\CoreBundle\Dca\Definition\Builder\DcaNodeBuilder;
use Contao\CoreBundle\Dca\Definition\Builder\FieldDefinition;
use Contao\CoreBundle\Dca\Definition\Builder\PreconfiguredDefinitionInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

class FieldDefinitionTest extends TestCase
{
    public function testIsPreconfigured(): void
    {
        $this->assertInstanceOf(PreconfiguredDefinitionInterface::class, new FieldDefinition(null));
    }

    public function testReturnsArrayNode(): void
    {
        $definition = new FieldDefinition(null);
        $definition->setBuilder(new DcaNodeBuilder());
        $definition->preconfigure();

        $node = $definition->getNode();

        $this->assertInstanceOf(ArrayNode::class, $node);
    }

    /**
     * @dataProvider sqlDataProvider
     */
    public function testAcceptsStringAndArraySql(mixed $sql, bool $valid): void
    {
        $definition = new FieldDefinition(null);
        $definition->setBuilder(new DcaNodeBuilder());
        $definition->preconfigure();

        $node = $definition->getNode();

        if (!$valid) {
            $this->expectException(InvalidConfigurationException::class);
        }

        $node->finalize([
            'sql' => $sql,
        ]);

        $this->assertTrue(true);
    }

    public function sqlDataProvider(): array
    {
        return [
            ['foo', true],
            [['name' => 'foo', 'type' => 'string', 'length' => 64], true],
            [123, false],
            [new \stdClass(), false],
        ];
    }
}
