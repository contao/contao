<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Undo;

use Contao\CoreBundle\Event\UndoDescriptionEvent;
use Contao\CoreBundle\EventListener\Undo\UndoDescriptionListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\TextField;
use Symfony\Component\VarDumper\VarDumper;

class UndoDescriptionListenerTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['TL_DCA'] = [];
    }

    /**
     * @dataProvider rowAndOptionsProvider
     */
    public function testGetDescriptionForRow(array $data, array $options, ?string $expected): void
    {
        $table = 'tl_member';
        $GLOBALS['TL_DCA'][$table]['list'] = ['undo' => $options];
        $listener = new UndoDescriptionListener();

        $event = new UndoDescriptionEvent($table, $data);
        $listener($event);

        $this->assertSame($event->getDescription(), $expected);
    }

    /**
     * @dataProvider rowAndOptionsForContentElementsProvider
     */
    public function testGetDescriptionForDifferentContentElements(array $data, array $options, ?string $expected): void
    {
        $table = 'tl_content';
        $GLOBALS['TL_DCA'][$table]['list'] = [
            'undo' => array_merge
            (
                ['discriminator' => 'type'],
                $options
            )
        ];
        $listener = new UndoDescriptionListener();

        $event = new UndoDescriptionEvent($table, $data);
        $listener($event);
        $this->assertSame($event->getDescription(), $expected);
    }

    public function rowAndOptionsProvider(): \Generator
    {
        yield 'Single field with format' => [
            $this->getTestData(),
            [
                'fields' => 'lastname',
                'format' => '%s',
            ],
            'Doe',
        ];

        yield 'Single field without format' => [
            $this->getTestData(),
            [
                'fields' => 'lastname',
            ],
            'Doe',
        ];

        yield 'Multiple fields with format' => [
            $this->getTestData(),
            [
                'fields' => ['firstname', 'lastname', 'company'],
                'format' => '%s %s (%s)',
            ],
            'John Doe (Acme Corp.)',
        ];

        yield 'Multiple fields without format' => [
            $this->getTestData(),
            [
                'fields' => ['firstname', 'lastname', 'company'],
            ],
            'John, Doe, Acme Corp.',
        ];

        yield 'Fallback to commonly used fields, if no options where defined' => [
            $this->getTestData(),
            [],
            'john.doe',
        ];

        yield 'Fallback to row ID, if no commonly used field is present' => [
            [
                'id' => 42,
                'firstname' => 'John',
                'lastname' => 'Doe',
            ],
            [],
            'ID 42',
        ];
    }

    public function rowAndOptionsForContentElementsProvider(): \Generator
    {
        $elementMap = [
            'fields' => [
                'headline' => 'headline',
                'text' => 'text',
                'image' => 'singleSRC',
                'gallery' => 'multiSRC',
                'hyperlink' => ['url', 'linkText'],
            ]
        ];

        yield 'Gets description for headline element' => [
            [
                'id' => 42,
                'type' => 'headline',
                'headline' => serialize(['unit' => 'h2', 'value' => 'This is a headline'])
            ],
            $elementMap,
            serialize(['unit' => 'h2', 'value' => 'This is a headline'])
        ];

        yield 'Gets description for text element' => [
            [
                'id' => 42,
                'type' => 'text',
                'text' => '<p>This is a test element</p>'
            ],
            $elementMap,
            '<p>This is a test element</p>'
        ];

        yield 'Gets description for hyperlink element' => [
            [
                'id' => 42,
                'type' => 'hyperlink',
                'url' => 'https://contao.org',
                'linkText' => 'This is a hyperlink element'
            ],
            $elementMap,
            'https://contao.org, This is a hyperlink element'
        ];

        yield 'Falls back to commonly used fields if no field is defined for element' => [
            [
                'id' => 42,
                'type' => 'my_custom_content_element',
                'headline' => serialize(['unit' => 'h2', 'value' => 'Element headline'])
            ],
            $elementMap,
            serialize(['unit' => 'h2', 'value' => 'Element headline'])
        ];

        yield 'Falls back to ID if no field is defined for element' => [
            [
                'id' => 42,
                'type' => 'my_custom_content_element'
            ],
            $elementMap,
            'ID 42'
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
            'company' => 'Acme Corp.',
        ];
    }
}
