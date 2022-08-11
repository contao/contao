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

use Contao\CoreBundle\Controller\ContentElement\HyperlinkController;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\RequestStack;

class HyperlinkControllerTest extends ContentElementTestCase
{
    public function testOutputsSimpleLink(): void
    {
        $response = $this->renderWithModelData(
            new HyperlinkController($this->getDefaultStudio(), $this->getDefaultInsertTagParser(), new RequestStack()),
            [
                'type' => 'hyperlink',
                'url' => 'my-link.html',
                'linkTitle' => '',
                'target' => false,
                'embed' => '',
                'useImage' => false,
                'singleSRC' => '',
                'size' => '',
                'rel' => '',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-hyperlink">
                <a href="/my-link.html">/my-link.html</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsLinkWithBeforeAndAfterText(): void
    {
        $response = $this->renderWithModelData(
            new HyperlinkController($this->getDefaultStudio(), $this->getDefaultInsertTagParser(), new RequestStack()),
            [
                'type' => 'hyperlink',
                'url' => 'https://www.php.net/manual/en/function.sprintf.php',
                'linkTitle' => 'sprintf() documentation',
                'target' => true,
                'embed' => 'See the %s on how to use the %s placeholder.',
                'useImage' => false,
                'singleSRC' => '',
                'size' => '',
                'rel' => '',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-hyperlink">
                See the <a href="https://www.php.net/manual/en/function.sprintf.php" title="sprintf() documentation" target="_blank" rel="noreferrer noopener">sprintf() documentation</a>
                on how to use the %s placeholder.
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsImageLink(): void
    {
        $response = $this->renderWithModelData(
            new HyperlinkController($this->getDefaultStudio(), $this->getDefaultInsertTagParser(), new RequestStack()),
            [
                'type' => 'hyperlink',
                'url' => 'foo.html#{{demo}}',
                'linkTitle' => '',
                'target' => false,
                'embed' => 'This is me… %s …waving to the camera.',
                'useImage' => true,
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                'size' => '',
                'rel' => 'bar',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-hyperlink">
                <figure>
                    This is me…
                    <a href="/foo.html#demo" data-lightbox="bar">
                        <img src="files/image1.jpg" alt>
                    </a>
                    …waving to the camera.
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
