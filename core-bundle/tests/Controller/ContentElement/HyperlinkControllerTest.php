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
use Symfony\Component\Asset\Context\RequestStackContext;

class HyperlinkControllerTest extends ContentElementTestCase
{
    public function testOutputsSimpleLink(): void
    {
        $requestStackContext = $this->createMock(RequestStackContext::class);
        $requestStackContext
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn('')
        ;

        $response = $this->renderWithModelData(
            new HyperlinkController($this->getDefaultStudio(), $this->getDefaultInsertTagParser(), $requestStackContext),
            [
                'type' => 'hyperlink',
                'url' => 'my-link.html',
                'linkTitle' => '',
                'target' => '',
                'embed' => '',
                'useImage' => '',
                'singleSRC' => '',
                'size' => '',
                'rel' => '',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/hyperlink">
                <a href="/my-link.html">/my-link.html</a>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsLinkWithBeforeAndAfterText(): void
    {
        $requestStackContext = $this->createMock(RequestStackContext::class);
        $requestStackContext
            ->expects($this->never())
            ->method('getBasePath')
        ;

        $response = $this->renderWithModelData(
            new HyperlinkController($this->getDefaultStudio(), $this->getDefaultInsertTagParser(), $requestStackContext),
            [
                'type' => 'hyperlink',
                'url' => 'https://www.php.net/manual/en/function.sprintf.php',
                'linkTitle' => 'sprintf() documentation',
                'target' => '1',
                'embed' => 'See the %s on how to use the %s placeholder.',
                'useImage' => '',
                'singleSRC' => '',
                'size' => '',
                'rel' => '',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/hyperlink">
                See the <a href="https://www.php.net/manual/en/function.sprintf.php" title="sprintf() documentation" target="_blank" rel="noreferrer noopener">sprintf() documentation</a>
                on how to use the %s placeholder.
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsImageLink(): void
    {
        $requestStackContext = $this->createMock(RequestStackContext::class);
        $requestStackContext
            ->expects($this->once())
            ->method('getBasePath')
            ->willReturn('')
        ;

        $response = $this->renderWithModelData(
            new HyperlinkController($this->getDefaultStudio(), $this->getDefaultInsertTagParser(), $requestStackContext),
            [
                'type' => 'hyperlink',
                'url' => 'foo.html#{{demo}}',
                'linkTitle' => '',
                'target' => '',
                'embed' => 'This is me… %s …waving to the camera.',
                'useImage' => '1',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                'size' => '',
                'rel' => 'bar',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content_element/hyperlink">
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
