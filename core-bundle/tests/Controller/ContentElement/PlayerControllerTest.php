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

use Contao\CoreBundle\Controller\ContentElement\PlayerController;

class PlayerControllerTest extends ContentElementTestCase
{
    public function testOutputsFigure(): void
    {
        $response = $this->renderWithModelData(
            new PlayerController($this->getDefaultStorage()),
            [
                'type' => 'player',
                'playerSRC' => serialize([
                    self::FILE_VIDEO_MP4,
                    self::FILE_VIDEO_OGV,
                ]),
                'playerOptions' => serialize([
                    'player_autoplay', 'player_loop',
                ]),
                'playerCaption' => 'Caption',
            ],
        );

        $expectedOutput = <<<'HTML'
            <div class="content-player">
                <figure>
                    <video controls autoplay loop>
                        <source src="https://example.com/files/video.mp4">
                        <source src="https://example.com/files/video.ogv">
                    </video>
                    <figcaption>Caption</figcaption>
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }

    public function testOutputsEmptyResponse(): void
    {
        $response = $this->renderWithModelData(
            new PlayerController($this->getDefaultStorage()),
            [
                'type' => 'player',
            ],
        );

        $this->assertEmpty($response->getContent());
    }

    public function testOutputsSummary(): void
    {
        $response = $this->renderWithModelData(
            new PlayerController($this->getDefaultStorage()),
            [
                'type' => 'player',
                'playerSRC' => serialize([
                    self::FILE_VIDEO_MP4,
                    self::FILE_VIDEO_OGV,
                ]),
            ],
            asEditorView: true,
        );

        $expectedOutput = <<<'HTML'
            <div class="content-player">
                <ul>
                    <li>
                        <span>video.mp4</span> <span class="size">(0.0 Byte)</span>
                    </li>
                    <li>
                        <span>video.ogv</span> <span class="size">(0.0 Byte)</span>
                    </li>
                </ul>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
