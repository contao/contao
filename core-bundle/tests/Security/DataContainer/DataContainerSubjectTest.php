<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Security\DataContainer;

use Contao\CoreBundle\Security\DataContainer\DataContainerSubject;
use PHPUnit\Framework\TestCase;

class DataContainerSubjectTest extends TestCase
{
    /**
     * @dataProvider idTypesProvider
     */
    public function testContainer(int|string|null $id): void
    {
        $subject = new DataContainerSubject('foobar_table', $id, ['foobar']);

        $this->assertSame('foobar_table', $subject->table);
        $this->assertSame($id, $subject->id);
        $this->assertSame(['foobar'], $subject->attributes);
    }

    public function testToString(): void
    {
        $subject = new DataContainerSubject(
            'foobar_table',
            '18e8880b-0442-4629-9651-9c667f6c1d4a',
            [
                'foobar' => null,
                'key' => 'value',
                'object' => new \stdClass(),
            ]
        );

        $this->assertSame(
            '[Subject: Table: foobar_table; ID: 18e8880b-0442-4629-9651-9c667f6c1d4a; Attributes: {"foobar":null,"key":"value","object":{}}]',
            (string) $subject
        );
    }

    public function idTypesProvider(): \Generator
    {
        yield ['18e8880b-0442-4629-9651-9c667f6c1d4a'];
        yield [42];
        yield [null];
    }
}
