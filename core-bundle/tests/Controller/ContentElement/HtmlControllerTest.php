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

use Contao\CoreBundle\Controller\ContentElement\HtmlController;

class HtmlControllerTest extends ContentElementTestCase
{
    public function testOutputsRawHtml(): void
    {
        $response = $this->renderWithModelData(
            new HtmlController(),
            [
                'type' => 'html',
                'html' => '<p>Hello{{br}}<b>world</b>!</p>',
            ]
        );

        $expectedOutput = '<p>Hello<br><b>world</b>!</p>';

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsSummary(): void
    {
        $response = $this->renderWithModelData(
            new HtmlController(),
            [
                'type' => 'html',
                'html' => '<p>Hello{{br}}<b>world</b>!</p>',
            ],
            asEditorView: true
        );

        $expectedOutput = '<pre>&lt;p&gt;Hello{{br}}&lt;b&gt;world&lt;/b&gt;!&lt;/p&gt;</pre>';

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
