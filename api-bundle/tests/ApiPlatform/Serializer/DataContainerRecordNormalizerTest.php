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

final class DataContainerRecordNormalizerTest extends TestCase
{
    public function testNormalizesTheRecordData(): void
    {
        $normalizer = new DataContainerRecordNormalizer();
        $record = new DataContainerRecord('tl_content', ['headline' => 'Example'], 17);

        $normalized = $normalizer->normalize($record);

        $this->assertSame(
            [
                'id' => 17,
                'headline' => 'Example',
            ],
            $normalized,
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
