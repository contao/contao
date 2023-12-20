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
use Symfony\Component\HttpFoundation\Request;

class AccordionControllerTest extends ContentElementTestCase
{
    public function testOutputsAccordion(): void
    {
        $model = $this->mockClassWithProperties(ContentModel::class, [
            'id' => 1,
            'type' => 'text',
            'sectionHeadline' => 'Section',
            'text' => '<p>Text.</p>',
        ]);

        $request = new Request();
        $request->attributes->set('nestedFragments', [new ContentElementReference($model)]);

        $response = $this->renderWithModelData(
            new AccordionController($this->getDefaultFramework($model)),
            [
                'type' => 'accordion',
            ],
            null,
            false,
            $responseContextData,
            null,
            $request,
        );

        $expectedOutput = <<<'HTML'
            <div class="content-accordion">
                <h3 class="handorgel__header">
                    <button class="handorgel__header__button">Section</button>
                </h3>
                <div class="handorgel__content">
                    <div class="handorgel__content__inner">
                        Content element
                    </div>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertArrayHasKey('handorgel_css', $responseContextData['head']);
        $this->assertArrayHasKey('handorgel_js', $responseContextData['body']);
    }
}
