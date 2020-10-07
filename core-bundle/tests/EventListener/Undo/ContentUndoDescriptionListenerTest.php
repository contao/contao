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
use Contao\CoreBundle\EventListener\Undo\ContentUndoDescriptionListener;
use Contao\CoreBundle\Tests\TestCase;

class ContentUndoDescriptionListenerTest extends TestCase
{
    public function testDoesNotHandleOtherThanContentTable(): void
    {
        $listener = new ContentUndoDescriptionListener();

        $event = new UndoDescriptionEvent('tl_foo', [], []);
        $listener($event);

        $this->assertSame($event->getDescription(), null);
    }

    public function testStopsPropagationIfDescriptionFound(): void
    {
        $listener = new ContentUndoDescriptionListener();

        $event = new UndoDescriptionEvent('tl_content', [
            'type' => 'headline',
            'headline' => ['unit' => 'h3', 'value' => 'This is a headline'],
        ], []);
        $listener($event);

        $this->assertTrue($event->isPropagationStopped());
        $this->assertSame($event->getDescription(), 'This is a headline');
    }

    public function testEventStillPropagatesIfNoDescriptionFound(): void
    {
        $listener = new ContentUndoDescriptionListener();

        $event = new UndoDescriptionEvent('tl_content', ['type' => 'myContentElement'], []);
        $listener($event);

        $this->assertFalse($event->isPropagationStopped());
        $this->assertSame($event->getDescription(), null);
    }

    /**
     * @dataProvider rowAndOptionsProvider
     */
    public function testGetDescriptionForRow(array $data, array $options, ?string $expected): void
    {
        $listener = new ContentUndoDescriptionListener();

        $event = new UndoDescriptionEvent('tl_content', $data, $options);
        $listener($event);

        $this->assertSame($event->getDescription(), $expected);
    }

    public function rowAndOptionsProvider(): \Generator
    {
        yield 'Not a supported content element' => [
            [
                'type' => 'myCustomContentElement',
            ],
            [],
            null,
        ];

        yield 'Headline' => [
            [
                'type' => 'headline',
                'headline' => serialize(['unit' => 'h1', 'value' => 'This is a headline']),
            ],
            [],
            'This is a headline',
        ];

        yield 'Text' => [
            [
                'type' => 'text',
                'text' => '<p>Contao is a powerful open source CMS.</p>',
            ],
            [],
            'Contao is a powerful open source CMS.',
        ];

        yield 'HTML' => [
            [
                'type' => 'html',
                'html' => '<p>Contao is a powerful open source CMS.</p>',
            ],
            [],
            htmlspecialchars('<p>Contao is a powerful open source CMS.</p>'),
        ];

        yield 'List' => [
            [
                'type' => 'list',
                'listitems' => serialize(['Foo', 'Bar', 'Baz']),
            ],
            [],
            'Foo, Bar, Baz',
        ];

        yield 'Table' => [
            [
                'type' => 'table',
                'tableitems' => serialize([['TH 1', 'TH 2', 'TH 3'], ['TD 1', 'TD 1', 'TD 1']]),
            ],
            [],
            'TH 1, TH 2, TH 3',
        ];

        yield 'AccordionStart with mooHeadline' => [
            [
                'type' => 'accordionStart',
                'mooHeadline' => 'An accordion section',
            ],
            [],
            'An accordion section',
        ];

        yield 'AccordionStart without mooHeadline' => [
            [
                'id' => 42,
                'type' => 'accordionStart',
                'mooHeadline' => '',
            ],
            [],
            'ID 42',
        ];

        yield 'AccordionStop' => [
            [
                'id' => 42,
                'type' => 'accordionStop',
            ],
            [],
            'ID 42',
        ];

        yield 'AccordionSingle with headline' => [
            [
                'id' => 42,
                'type' => 'accordionSingle',
                'mooHeadline' => 'This is a section headline',
                'text' => '<p>This is the section text.</p>',
            ],
            [],
            'This is a section headline',
        ];

        yield 'AccordionSingle without headline' => [
            [
                'id' => 42,
                'type' => 'accordionSingle',
                'mooHeadline' => '',
                'text' => '<p>This is the section text.</p>',
            ],
            [],
            htmlspecialchars('<p>This is the section text.</p>'),
        ];

        yield 'SliderStart' => [
            [
                'id' => 42,
                'type' => 'sliderStart',
            ],
            [],
            'ID 42',
        ];

        yield 'SliderStop' => [
            [
                'id' => 42,
                'type' => 'sliderStop',
            ],
            [],
            'ID 42',
        ];

        $codeSnippet = <<<'CODE_SNIPPET'
<?php declare(strict_types=1);
// Comment
echo 'This is a code snippet';
CODE_SNIPPET;

        yield 'Code' => [
            [
                'type' => 'code',
                'code' => $codeSnippet,
            ],
            [],
            htmlspecialchars($codeSnippet),
        ];

        $markdownSnippet = <<<'MARKDOWN_SNIPPET'
# This is a headline
This is a markdown paragraph.
MARKDOWN_SNIPPET;

        yield 'Markdown' => [
            [
                'type' => 'markdown',
                'markdown' => $markdownSnippet,
            ],
            [],
            $markdownSnippet,
        ];

        yield 'Hyperlink' => [
            [
                'type' => 'hyperlink',
                'url' => 'https://contao.org',
            ],
            [],
            'https://contao.org',
        ];

        yield 'Toplink with linkTitle' => [
            [
                'id' => 42,
                'type' => 'toplink',
                'linkTitle' => 'Nach oben',
            ],
            [],
            'Nach oben',
        ];

        yield 'Toplink without linkTitle' => [
            [
                'id' => 42,
                'type' => 'toplink',
                'linkTitle' => '',
            ],
            [],
            'ID 42',
        ];

        // TODO: Image
        // TODO: Gallery
        // TODO: Player

        yield 'Youtube' => [
            [
                'id' => 42,
                'type' => 'youtube',
                'youtube' => 'https://www.youtube.com/watch?v=REboWtl5gmE',
            ],
            [],
            'https://www.youtube.com/watch?v=REboWtl5gmE',
        ];

        yield 'Vimeo' => [
            [
                'id' => 42,
                'type' => 'vimeo',
                'vimeo' => 'https://vimeo.com/275028611',
            ],
            [],
            'https://vimeo.com/275028611',
        ];

        // TODO: Download
        // TODO: Downloads
        // TODO: Alias
        // TODO: Article
        // TODO: Teaser
        // TODO: Form
        // TODO: Module
    }
}
