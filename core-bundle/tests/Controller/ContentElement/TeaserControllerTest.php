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

use Contao\CoreBundle\Controller\ContentElement\TeaserController;

class TeaserControllerTest extends ContentElementTestCase
{
    public function testOutputsTeaser(): void
    {
        $response = $this->renderWithModelData(
            new TeaserController(),
            [
                'type' => 'teaser',
                'article' => self::ARTICLE1,
            ]
        );

        $expectedOutput = <<<'HTML'
            <div class="content-teaser">
                <p>This will tease you to read article 1.</p>
                <a href title="translated(contao_default:MSC.readMore[A title])">translated(contao_default:MSC.more)</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testDoesNotOutputTeaserIfPageDoesNotExist(): void
    {
        $response = $this->renderWithModelData(
            new TeaserController(),
            [
                'type' => 'teaser',
                'article' => 789,
            ]
        );

        $this->assertEmpty($response->getContent());
    }
}
