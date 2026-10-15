<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\DataContainer;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\DataContainer\DataContainerFieldContext;
use Contao\ApiBundle\DataContainer\DataContainerRelationDefinition;
use Contao\ApiBundle\DataContainer\DataContainerRelationDefinitionFactory;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Widget\RelationAwareWidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;

final class DataContainerRelationDefinitionFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);
    }

    public function testUsesTheDefinitionProvidedByAWidget(): void
    {
        $relation = new \stdClass();

        $converter = $this->createStub(RelationAwareWidgetConverterInterface::class);
        $converter
            ->method('supports')
            ->willReturn(true)
        ;

        $converter
            ->method('getRelation')
            ->willReturn($relation)
        ;

        $factory = $this->createFactory(new WidgetConverterRegistry([$converter]));

        $this->assertSame($relation, $factory->create(new DataContainerFieldContext(['inputType' => 'custom'])));
    }

    public function testUsesConfiguredAndForeignKeyRelations(): void
    {
        $factory = $this->createFactory(new WidgetConverterRegistry([]));

        $this->assertEquals(new DataContainerRelationDefinition('tl_page', 'alias'), $factory->create(new DataContainerFieldContext(['relation' => ['table' => 'tl_page', 'field' => 'alias']])));
        $this->assertEquals(new DataContainerRelationDefinition('tl_page'), $factory->create(new DataContainerFieldContext(['relation' => ['type' => 'hasOne'], 'foreignKey' => 'tl_page.title'])));
        $this->assertNull($factory->create(new DataContainerFieldContext(['inputType' => 'text'])));
    }

    public function testInfersParentAndTreeRelations(): void
    {
        $GLOBALS['TL_DCA']['tl_article']['config']['ptable'] = 'tl_page';
        $GLOBALS['TL_DCA']['tl_page']['list']['sorting']['mode'] = DataContainer::MODE_TREE;
        $factory = $this->createFactory(new WidgetConverterRegistry([]));

        $this->assertEquals(new DataContainerRelationDefinition('tl_page'), $factory->create(new DataContainerFieldContext([], 'tl_article', 'pid')));
        $this->assertEquals(new DataContainerRelationDefinition('tl_page'), $factory->create(new DataContainerFieldContext([], 'tl_page', 'pid')));
        $this->assertNull($factory->create(new DataContainerFieldContext([], 'tl_page', 'title')));
    }

    public function testUsesTheRowForDynamicParentsAndProvidesSchemaCandidates(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['config']['dynamicPtable'] = true;
        $factory = $this->createFactory(new WidgetConverterRegistry([]));
        $field = new DataContainerFieldContext([], 'tl_content', 'pid');

        $this->assertNull($factory->create($field));
        $this->assertEquals(new DataContainerRelationDefinition('tl_article'), $factory->create($field, ['ptable' => 'tl_article']));
        $this->assertEquals([new DataContainerRelationDefinition('tl_page')], iterator_to_array($factory->createForSchema($field)));
    }

    private function createFactory(WidgetConverterRegistry $converters): DataContainerRelationDefinitionFactory
    {
        $metadata = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadata
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, [
                new ApiResource(operations: [new Get(name: 'page_get')], extraProperties: ['contao' => ['table' => 'tl_page']]),
                new ApiResource(operations: [], extraProperties: ['contao' => ['table' => 'tl_unexposed']]),
            ]))
        ;

        return new DataContainerRelationDefinitionFactory(new ForeignKeyParser($this->createStub(Connection::class)), $converters, $metadata);
    }
}
