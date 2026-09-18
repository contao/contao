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

use ApiPlatform\Metadata\Get;
use Contao\ApiBundle\ApiPlatform\Serializer\DataContainerRecordNormalizer;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class DataContainerRecordNormalizerTest extends TestCase
{
    public function testKeepsOnlyExplicitlySubmittedFieldsForTheUpdate(): void
    {
        $record = new DataContainerRecord('tl_content', ['title' => 'Example', 'published' => false, 'tags' => ['old', 'other']], 17);
        $normalizer = new DataContainerRecordNormalizer();

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
        $normalizer = new DataContainerRecordNormalizer();

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

        new DataContainerRecordNormalizer()->denormalize(
            ['id' => 18],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => new DataContainerRecord('tl_content', [], 17)],
        );
    }

    public function testAcceptsTheExistingIdentifierAsAString(): void
    {
        $record = new DataContainerRecord('tl_content', ['title' => 'Example'], 17);

        $result = new DataContainerRecordNormalizer()->denormalize(
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

        new DataContainerRecordNormalizer()->denormalize(
            ['title' => 'Example'],
            DataContainerRecord::class,
            context: ['contao_table' => 'tl_content', AbstractNormalizer::OBJECT_TO_POPULATE => new DataContainerRecord('tl_news', [], 17)],
        );
    }

    public function testNormalizesTheRecordData(): void
    {
        $normalizer = new DataContainerRecordNormalizer();
        $record = new DataContainerRecord('tl_content', ['headline' => 'Example'], 17);

        $this->assertSame(
            [
                'id' => 17,
                'headline' => 'Example',
            ],
            $normalizer->normalize($record),
        );
    }

    public function testDenormalizesTheRecordDataUsingTheOperationTable(): void
    {
        $normalizer = new DataContainerRecordNormalizer();

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

        $this->assertInstanceOf(DataContainerRecord::class, $record);
        $this->assertSame('tl_content', $record->table);
        $this->assertSame(17, $record->id);
        $this->assertSame(['headline' => 'Example'], $record->data);
    }

    public function testSupportsOnlyTheDataContainerRecordClass(): void
    {
        $normalizer = new DataContainerRecordNormalizer();

        $this->assertTrue($normalizer->supportsNormalization(new DataContainerRecord('tl_content')));
        $this->assertTrue($normalizer->supportsDenormalization([], DataContainerRecord::class));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
        $this->assertFalse($normalizer->supportsDenormalization([], \stdClass::class));
    }

    public function testDenormalizingWithoutATableFails(): void
    {
        $normalizer = new DataContainerRecordNormalizer();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('without a Contao table');

        $normalizer->denormalize(['headline' => 'Example'], DataContainerRecord::class);
    }
}
