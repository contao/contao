<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener;

use Contao\CoreBundle\Event\UndoDescriptionEvent;
use Contao\CoreBundle\EventListener\Undo\UndoDescriptionListener;
use Contao\CoreBundle\Tests\TestCase;
use Generator;

class UndoDescriptionListenerTest extends TestCase
{
    /**
     * @var array
     */
    private $data;

    protected function setUp(): void
    {
        $this->data = $this->getTestData();

        parent::setUp();
    }

    /**
     * @dataProvider getDataAndOptionsProvider
     * @param array $data
     * @param array $options
     * @param string $expected
     */
    public function testGetDescriptionForRow(array $data, array $options, string $expected): void
    {
        $listener = new UndoDescriptionListener();

        $event = new UndoDescriptionEvent('tl_member', $data, $options);
        $listener($event);

        $this->assertSame($event->getDescription(), $expected);
    }

    public function getDataAndOptionsProvider(): Generator
    {
        yield 'Single field with format' => [
            $this->getTestData(),
            [
                'fields' => 'lastname',
                'format' => '%s'
            ],
            'Doe'
        ];

        yield 'Single field without format' => [
            $this->getTestData(),
            [
                'fields' => 'lastname'
            ],
            'Doe'
        ];

        yield 'Multiple fields with format' => [
            $this->getTestData(),
            [
                'fields' => ['firstname', 'lastname', 'company'],
                'format' => '%s %s (%s)'
            ],
            'John Doe (Acme Corp.)'
        ];

        yield 'Multiple fields without format' => [
            $this->getTestData(),
            [
                'fields' => ['firstname', 'lastname', 'company']
            ],
            'John, Doe, Acme Corp.'
        ];

        yield 'Fallback to commonly used fields, if no options where defined' => [
            $this->getTestData(),
            [],
            'john.doe'
        ];

        yield 'Fallback to row ID, if no commonly used field is present' => [
            [
                'id' => 42,
                'firstname' => 'John',
                'lastname' => 'Doe'
            ],
            [],
            '42'
        ];
    }

    private function getTestData(): array
    {
        return [
            'id' => 42,
            'firstname' => 'John',
            'lastname' => 'Doe',
            'email' => 'john.doe@acmecorp.com',
            'username' => 'john.doe',
            'company' => 'Acme Corp.'
        ];
    }
}
