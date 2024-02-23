<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Controller\ContentElement;

use Contao\CoreBundle\Controller\ContentElement\ListController;

class ListControllerTest extends ContentElementTestCase
{
    public function testOutputsOrderedList(): void
    {
        $response = $this->renderWithModelData(
            new ListController(),
            [
                'type' => 'list',
                'listitems' => serialize(['first', 'second', 'third']),
                'listtype' => 'ordered',
                'headline' => ['unit' => 'h2', 'value' => 'Ordered list'],
            ],
        );

        $expectedOutput = <<<'EOT'
            <div class="content-list">
                <h2>Ordered list</h2>
                <ol>
                    <li>first</li>
                    <li>second</li>
                    <li>third</li>
                </ol>
            </div>
            EOT;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsUnorderedList(): void
    {
        $response = $this->renderWithModelData(
            new ListController(),
            [
                'type' => 'list',
                'listitems' => serialize(['foo', 'bar{{br}}baz <i>HTML</i>']),
                'listtype' => 'unordered',
                'cssID' => serialize(['', 'my-class']),
            ],
        );

        $expectedOutput = <<<'EOT'
            <div class="my-class content-list">
                <ul>
                    <li>foo</li>
                    <li>bar<br>baz <i>HTML</i></li>
                </ul>
            </div>
            EOT;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
