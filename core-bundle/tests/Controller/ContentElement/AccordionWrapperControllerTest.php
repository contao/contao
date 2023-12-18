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

use Contao\CoreBundle\Controller\ContentElement\AccordionWrapperController;
use Contao\CoreBundle\Controller\ContentElement\TextController;
use Symfony\Component\HttpFoundation\Request;

class AccordionWrapperControllerTest extends ContentElementTestCase
{
    public function testOutputsMarkup(): void
    {
        $nested = $this->renderWithModelData(
            new TextController($this->getDefaultStudio()),
            [
                'type' => 'text',
                'text' => '<p>Foo.</p>',
            ],
        );

        $request = new Request();
        $request->attributes->set('nestedFragments', [$nested]);

        $response = $this->renderWithModelData(
            new AccordionWrapperController(),
            [
                'type' => 'accordion_wrapper',
                'mooHeadline' => 'First section',
            ],
            null,
            false,
            $responseContextData,
            null,
            $request,
        );

        $expectedOutput = <<<'HTML'
            <section class="content-accordion content-accordion_wrapper">
                <div class="toggler">
                    First section
                </div>
                <div class="accordion">
                    <div>
                        <div class="content-text">
                            <div class="rte">
                                <p>Foo.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
