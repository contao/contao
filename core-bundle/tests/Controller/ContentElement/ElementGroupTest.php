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

class ElementGroupTest extends ContentElementTestCase
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
                'type' => 'element_group',
            ],
            null,
            false,
            $responseContextData,
            null,
            $request,
        );

        $expectedOutput = <<<'HTML'
            <div class="content-element_group">
                Content element
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertEmpty($responseContextData);
    }
}
