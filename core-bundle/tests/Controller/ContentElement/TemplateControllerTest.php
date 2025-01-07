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

use Contao\CoreBundle\Controller\ContentElement\TemplateController;

class TemplateControllerTest extends ContentElementTestCase
{
    public function testOutputsData(): void
    {
        $response = $this->renderWithModelData(
            new TemplateController(),
            [
                'type' => 'template',
                'data' => serialize([
                    ['key' => 'f&o', 'value' => '"bar"'],
                    ['key' => 'lorem', 'value' => 'ipsum'],
                ]),
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-template">
                <dl>
                    <dt>f&amp;o</dt>
                    <dd>&quot;bar&quot;</dd>
                    <dt>lorem</dt>
                    <dd>ipsum</dd>
                </dl>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
