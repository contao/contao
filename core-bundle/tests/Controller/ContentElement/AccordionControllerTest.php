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
use Contao\CoreBundle\Controller\ContentElement\TextController;
use Symfony\Component\HttpFoundation\Request;

class AccordionControllerTest extends ContentElementTestCase
{
    public function testRendersSingleElement(): void
    {
        $response = $this->renderWithModelData(
            new AccordionController($this->getDefaultStudio()),
            [
                'type' => 'accordion_single',
                'mooHeadline' => 'First section',
                'text' => '<p>This is the text.</p>',
            ],
            'content_element/accordion',
            false,
            $responseContextData,
        );

        $expectedOutput = <<<'HTML'
            <section class="content-accordion content-accordion_single">
                <div class="handorgel__header">First section</div>
                <div class="handorgel__content"><div>
                    <div class="rte">
                        <p>This is the text.</p>
                    </div>
                </div></div>
            </section>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertArrayHasKey('handorgel_css', $responseContextData['head']);
        $this->assertArrayHasKey('handorgel_js', $responseContextData['body']);
    }

    public function testRendersWrapperElement(): void
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
            new AccordionController($this->getDefaultStudio()),
            [
                'type' => 'accordion_wrapper',
                'mooHeadline' => 'First section',
            ],
            'content_element/accordion',
            false,
            $responseContextData,
            null,
            $request,
        );

        $expectedOutput = <<<'HTML'
            <section class="content-accordion content-accordion_wrapper">
                <div class="handorgel__header">First section</div>
                <div class="handorgel__content"><div>
                    <div class="content-text">
                        <div class="rte">
                            <p>Foo.</p>
                        </div>
                    </div>
                </div></div>
            </section>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertArrayHasKey('handorgel_css', $responseContextData['head']);
        $this->assertArrayHasKey('handorgel_js', $responseContextData['body']);
    }
}
