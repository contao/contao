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

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AccordionController;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;

class AccordionControllerTest extends ContentElementTestCase
{
    public function testOutputsAccordion(): void
    {
        $text = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'text',
            'sectionHeadline' => 'Text',
        ]);

        $image = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'image',
            'sectionHeadline' => 'Image',
        ]);

        $response = $this->renderWithModelData(
            new AccordionController($this->getDefaultFramework()),
            [
                'type' => 'accordion',
                'closeSections' => false,
            ],
            null,
            false,
            $responseContextData,
            null,
            [
                new ContentElementReference($text, 'main', [], true),
                new ContentElementReference($image, 'main', [], true),
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-accordion">
                <h3 class="handorgel__header">
                    <button class="handorgel__header__button">Text</button>
                </h3>
                <div class="handorgel__content" data-open>
                    <div class="handorgel__content__inner">
                        text
                    </div>
                </div>
                <h3 class="handorgel__header">
                    <button class="handorgel__header__button">Image</button>
                </h3>
                <div class="handorgel__content">
                    <div class="handorgel__content__inner">
                        image
                    </div>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertArrayHasKey('handorgel_css', $responseContextData['head']);
        $this->assertArrayHasKey('handorgel_js', $responseContextData['body']);
    }

    public function testDoesNotAddTheDataOpenAttribute(): void
    {
        $text = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'text',
            'sectionHeadline' => 'Text',
        ]);

        $image = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'image',
            'sectionHeadline' => 'Image',
        ]);

        $response = $this->renderWithModelData(
            new AccordionController($this->getDefaultFramework()),
            [
                'type' => 'accordion',
                'closeSections' => true,
            ],
            null,
            false,
            $responseContextData,
            null,
            [
                new ContentElementReference($text, 'main', [], true),
                new ContentElementReference($image, 'main', [], true),
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-accordion">
                <h3 class="handorgel__header">
                    <button class="handorgel__header__button">Text</button>
                </h3>
                <div class="handorgel__content">
                    <div class="handorgel__content__inner">
                        text
                    </div>
                </div>
                <h3 class="handorgel__header">
                    <button class="handorgel__header__button">Image</button>
                </h3>
                <div class="handorgel__content">
                    <div class="handorgel__content__inner">
                        image
                    </div>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertArrayHasKey('handorgel_css', $responseContextData['head']);
        $this->assertArrayHasKey('handorgel_js', $responseContextData['body']);
    }
}
