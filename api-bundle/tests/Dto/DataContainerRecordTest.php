<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\Dto;

use ApiPlatform\Metadata\ApiProperty;
use Contao\ApiBundle\Dto\DataContainerRecord;
use PHPUnit\Framework\TestCase;

final class DataContainerRecordTest extends TestCase
{
    public function testConvertsToAndFromArrays(): void
    {
        $record = DataContainerRecord::fromArray('tl_content', ['headline' => 'Example'], 17);

        $this->assertSame('tl_content', $record->table);
        $this->assertSame(17, $record->id);
        $this->assertSame(
            [
                'id' => 17,
                'headline' => 'Example',
            ],
            $record->toArray(),
        );
    }

    public function testMarksTheIdentifierPropertyForApiPlatform(): void
    {
        $property = new \ReflectionProperty(DataContainerRecord::class, 'id');
        $attributes = $property->getAttributes(ApiProperty::class);

        $this->assertCount(1, $attributes);
        $this->assertTrue($attributes[0]->newInstance()->isIdentifier());
    }
}
