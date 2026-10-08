<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\ApiPlatform\Serializer;

use ApiPlatform\JsonLd\AnonymousContextBuilderInterface;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\ApiPlatform\Serializer\DataContainerRecordNormalizer;
use Contao\ApiBundle\DataContainer\DataContainerRelationReference;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class DataContainerRecordNormalizerTest extends TestCase
{
    public function testKeepsOnlyExplicitlySubmittedFieldsForTheUpdate(): void
    {
        $record = new DataContainerRecord('tl_content', ['title' => 'Example', 'published' => false, 'tags' => ['old', 'other']], 17);
        $normalizer = $this->createNormalizer();

        $result = $normalizer->denormalize(
            ['published' => true, 'tags' => ['new']],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => $record],
        );

        $this->assertSame($record, $result);
        $this->assertSame(17, $result->id);
        $this->assertSame(['published' => true, 'tags' => ['new']], $result->data);
    }

    public function testPreservesExplicitNullValuesForValidation(): void
    {
        $record = new DataContainerRecord('tl_content', ['title' => 'Example'], 17);
        $normalizer = $this->createNormalizer();

        $result = $normalizer->denormalize(
            ['title' => null],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => $record],
        );

        $this->assertSame(['title' => null], $result->data);
    }

    public function testRejectsChangingTheExistingRecordIdentifier(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->createNormalizer()->denormalize(
            ['id' => 18],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => new DataContainerRecord('tl_content', [], 17)],
        );
    }

    public function testAcceptsTheExistingIdentifierAsAString(): void
    {
        $record = new DataContainerRecord('tl_content', ['title' => 'Example'], 17);

        $result = $this->createNormalizer()->denormalize(
            ['id' => '17'],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => $record],
        );

        $this->assertSame($record, $result);
        $this->assertSame([], $result->data);
    }

    public function testRejectsPopulatingARecordFromAnotherTable(): void
    {
        $this->expectException(NotNormalizableValueException::class);

        $this->createNormalizer()->denormalize(
            ['title' => 'Example'],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => new DataContainerRecord('tl_news', [], 17)],
        );
    }

    public function testNormalizesTheRecordData(): void
    {
        $normalizer = $this->createNormalizer();

        $record = new DataContainerRecord(
            'tl_content',
            [
                'headline' => 'Example',
                'jumpTo' => new DataContainerRelationReference(42, '/contao/api/dc/content/42'),
            ],
            17,
        );

        $this->assertSame(
            [
                'id' => [
                    'id' => 17,
                    'iri' => '/contao/api/dc/content/17',
                ],
                'headline' => 'Example',
                'jumpTo' => [
                    'id' => 42,
                    'iri' => '/contao/api/dc/content/42',
                ],
            ],
            $normalizer->normalize($record),
        );
    }

    public function testAddsTheResourceIriForJsonLd(): void
    {
        $normalizer = $this->createNormalizer();

        $record = new DataContainerRecord(
            'tl_content',
            [
                'headline' => 'Example',
                'jumpTo' => new DataContainerRelationReference(42, '/contao/api/dc/content/42'),
            ],
            17,
        );

        $this->assertSame(
            [
                '@context' => '/contao/api/contexts/Content',
                '@id' => '/contao/api/dc/content/17',
                '@type' => 'Content',
                'id' => [
                    '@id' => '/contao/api/dc/content/17',
                    'id' => 17,
                ],
                'headline' => 'Example',
                'jumpTo' => [
                    '@id' => '/contao/api/dc/content/42',
                    'id' => 42,
                ],
            ],
            $normalizer->normalize($record, 'jsonld'),
        );
    }

    public function testDenormalizesJsonLdRelationReferences(): void
    {
        $record = $this->createNormalizer()->denormalize(
            [
                '@context' => '/contao/api/contexts/Content',
                '@id' => '/contao/api/dc/content/17',
                '@type' => 'Content',
                'id' => ['@id' => '/contao/api/dc/content/17', 'id' => 17],
                'jumpTo' => ['@id' => '/contao/api/dc/content/42', 'id' => 42],
            ],
            DataContainerRecord::class,
            'jsonld',
            ['contao_table' => 'tl_content'],
        );

        $this->assertSame(['jumpTo' => ['id' => 42, 'iri' => '/contao/api/dc/content/42']], $record->data);
    }

    public function testDenormalizesTheRecordDataUsingTheOperationTable(): void
    {
        $normalizer = $this->createNormalizer();

        $operation = new Get()->withExtraProperties([
            'contao' => [
                'table' => 'tl_content',
            ],
        ]);

        $record = $normalizer->denormalize(
            ['id' => 17, 'headline' => 'Example'],
            DataContainerRecord::class,
            context: ['operation' => $operation],
        );

        $this->assertSame('tl_content', $record->table);
        $this->assertSame(17, $record->id);
        $this->assertSame(['headline' => 'Example'], $record->data);
    }

    public function testSupportsOnlyTheDataContainerRecordClass(): void
    {
        $normalizer = $this->createNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new DataContainerRecord('tl_content')));
        $this->assertTrue($normalizer->supportsDenormalization([], DataContainerRecord::class));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
        $this->assertFalse($normalizer->supportsDenormalization([], \stdClass::class));
    }

    public function testDenormalizingWithoutATableFails(): void
    {
        $normalizer = $this->createNormalizer();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('without a Contao table');

        $normalizer->denormalize(['headline' => 'Example'], DataContainerRecord::class);
    }

    private function createNormalizer(): DataContainerRecordNormalizer
    {
        $connection = $this->createStub(Connection::class);

        $metadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadataFactory
            ->method('create')
            ->willReturn(new ResourceMetadataCollection(DataContainerRecord::class, [
                new ApiResource(
                    operations: [new Get(name: 'content_get', extraProperties: ['contao' => ['parents' => []]])],
                    extraProperties: ['contao' => ['table' => 'tl_content']],
                ),
            ]))
        ;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $route, array $parameters): string => '/contao/api/dc/content/'.$parameters['id'])
        ;

        $contextBuilder = $this->createStub(AnonymousContextBuilderInterface::class);
        $contextBuilder
            ->method('getAnonymousResourceContext')
            ->willReturnCallback(static fn (DataContainerRecord $record, array $context): array => [
                '@context' => '/contao/api/contexts/Content',
                '@id' => $context['iri'],
                '@type' => 'Content',
            ])
        ;

        return new DataContainerRecordNormalizer(
            new DataContainerRelationResolver(
                $connection,
                new ForeignKeyParser($connection),
                new WidgetConverterRegistry([]),
                $metadataFactory,
                $router,
                $this->createStub(DcaHierarchy::class),
            ),
            $contextBuilder,
        );
    }
}
