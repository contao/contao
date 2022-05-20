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

use Contao\CoreBundle\Controller\ContentElement\HeadlineController;

class HeadlineControllerTest extends ContentElementTestCase
{
    public function testOutputsHeadline(): void
    {
        $response = $this->renderWithModelData(
            new HeadlineController(),
            [
                'type' => 'headline',
                'headline' => serialize(['unit' => 'h2', 'value' => 'My Headline']),
            ]
        );

        $expectedOutput = '<h2>My Headline</h2>';

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
