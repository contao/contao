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

class ElementGroupTest extends ContentElementTestCase
{
    public function testOutputsAccordion(): void
    {
        $text = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'text',
        ]);

        $image = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'image',
        ]);

        $response = $this->renderWithModelData(
            new AccordionController($this->getDefaultFramework()),
            [
                'type' => 'element_group',
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
            <div class="content-element-group">
                text
                image
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertEmpty($responseContextData);
    }
}
