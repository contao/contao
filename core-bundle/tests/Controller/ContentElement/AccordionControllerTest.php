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

use Contao\CoreBundle\Controller\ContentElement\AccordionController;

class AccordionControllerTest extends ContentElementTestCase
{
    public function testOutputsMarkup(): void
    {
        $response = $this->renderWithModelData(
            new AccordionController($this->getDefaultStudio()),
            [
                'type' => 'accordion',
                'mooHeadline' => 'First section',
                'text' => '<p>This is the text.</p>'
            ],
        );

        $expectedOutput = <<<'HTML'
            <section class="content-accordion">
                <div class="toggler">
                    First section
                </div>
                <div class="accordion">
                    <div>
                        <div class="rte">
                            <p>This is the text.</p>
                        </div>
                    </div>
                </div>
            </section>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
