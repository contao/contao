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

        $expectedOutput = '<h2 class="content-headline">My Headline</h2>';

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsHeadlineWithDefaultLevel(): void
    {
        $response = $this->renderWithModelData(
            new HeadlineController(),
            [
                'type' => 'headline',
                'headline' => serialize(['value' => 'My Headline']),
            ]
        );

        $expectedOutput = '<h1 class="content-headline">My Headline</h1>';

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsHeadlineWithCustomIdAndClass(): void
    {
        $response = $this->renderWithModelData(
            new HeadlineController(),
            [
                'type' => 'headline',
                'headline' => serialize(['unit' => 'h2', 'value' => 'My Headline']),
                'cssID' => serialize(['custom-id', 'custom-class']),
            ]
        );

        $expectedOutput = '<h2 id="custom-id" class="custom-class content-headline">My Headline</h2>';

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
