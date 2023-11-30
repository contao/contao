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

use Contao\CoreBundle\Controller\ContentElement\TextController;
use Contao\StringUtil;

class TextControllerTest extends ContentElementTestCase
{
    public function testOutputsText(): void
    {
        $response = $this->renderWithModelData(
            new TextController($this->getDefaultStudio()),
            [
                'type' => 'text',
                'text' => '<p>This is <b>rich</b>{{br}}text.</p> <p>There might be multiple paragraphs.</p>',
            ]
        );

        $expectedOutput = <<<'HTML'
            <div class="content-text">
                <div class="rte">
                    <p>This is <b>rich</b><br>text.</p>
                    <p>There might be multiple paragraphs.</p>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    /**
     * @dataProvider provideMediaPositions
     */
    public function testOutputsTextWithImage(string $floatingProperty, string $classes): void
    {
        $response = $this->renderWithModelData(
            new TextController($this->getDefaultStudio()),
            [
                'type' => 'text',
                'text' => '<p>Text</p>',
                'addImage' => true,
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                'size' => '',
                'fullsize' => false,
                'floating' => $floatingProperty,
            ]
        );

        $expectedOutput = <<<HTML
            <div class="$classes content-text">
                <figure>
                    <img src="files/image1.jpg" alt>
                </figure>
                <div class="rte">
                    <p>Text</p>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testEncodesEmailAddresses(): void
    {
        $response = $this->renderWithModelData(
            new TextController($this->getDefaultStudio()),
            [
                'type' => 'text',
                'text' => '<p>Write to foo@bar.com.</p>',
            ]
        );

        $expectedOutput = <<<'HTML'
            <div class="content-text">
                <div class="rte">
                    <p>Write to &#102;&#x6F;&#111;&#x40;&#98;&#x61;&#114;&#x2E;&#99;&#x6F;&#109;.</p>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function provideMediaPositions(): \Generator
    {
        yield 'above' => ['above', 'media media--above'];
        yield 'left' => ['left', 'media media--left'];
        yield 'right' => ['right', 'media media--right'];
        yield 'below' => ['below', 'media media--below'];
    }
}
