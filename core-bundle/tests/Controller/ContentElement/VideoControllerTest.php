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

use Contao\CoreBundle\Controller\ContentElement\VideoController;
use Contao\CoreBundle\Twig\ResponseContext\DocumentLocation;
use Contao\StringUtil;

class VideoControllerTest extends ContentElementTestCase
{
    public function testOutputsYoutubeVideoWithCaption(): void
    {
        $response = $this->renderWithModelData(
            new VideoController($this->getDefaultStudio()),
            [
                'type' => 'youtube',
                'playerSize' => '',
                'playerAspect' => '4:3',
                'youtube' => '12345678',
                'youtubeOptions' => serialize([
                    'youtube_nocookie',
                    'youtube_fs',
                    'youtube_iv_load_policy',
                    'youtube_loop',
                ]),
                'playerStart' => 15,
                'playerStop' => 60,
                'playerCaption' => 'Some caption',
            ],
            null,
            false,
            $responseContextData
        );

        $expectedOutput = <<<'HTML'
            <div class="content-youtube">
                <figure class="aspect aspect--4:3">
                    <iframe
                        width="640"
                        height="360"
                        src="https://www.youtube-nocookie.com/embed/12345678?fs=0&amp;iv_load_policy=3&amp;loop=1&amp;start=15&amp;end=60"
                        allowfullscreen
                        allow="autoplay; encrypted-media; picture-in-picture; fullscreen"></iframe>
                    <figcaption>Some caption</figcaption>
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertEmpty($responseContextData);
    }

    public function testOutputsVimeoVideoWithSplashScreen(): void
    {
        $response = $this->renderWithModelData(
            new VideoController($this->getDefaultStudio()),
            [
                'type' => 'vimeo',
                'playerSize' => serialize([1600, 900]),
                'playerAspect' => '',
                'vimeo' => '12345678',
                'vimeoOptions' => serialize([
                    'vimeo_autoplay',
                    'vimeo_portrait',
                ]),
                'playerColor' => 'f47c00',
                'playerStart' => 30,
                'splashImage' => '1',
                'singleSRC' => StringUtil::uuidToBin(ContentElementTestCase::FILE_IMAGE1),
                'size' => '',
            ],
            null,
            false,
            $responseContextData
        );

        $expectedOutput = <<<'HTML'
            <div class="content-vimeo">
                <figure>
                <button data-splash-screen>
                    <img src="files/image1.jpg" alt>
                    <p>translated(contao_default:MSC.splashScreen) translated(contao_default:MSC.dataTransmission[Vimeo])</p>
                    <template>
                        <iframe
                            width="1600"
                            height="900"
                            src="https://player.vimeo.com/video/12345678?autoplay=1&amp;portrait=0&amp;color=f47c00#t=30s"
                            allowfullscreen></iframe>
                    </template>
                </button>
                </figure>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());

        $additionalBodyCode = $responseContextData[DocumentLocation::endOfBody->value];

        $this->assertCount(1, $additionalBodyCode);
        $this->assertMatchesRegularExpression(
            '/<script>[^<]+button\.insertAdjacentHTML[^<]+<\/script>/',
            $additionalBodyCode['splash_screen_script']
        );
    }
}
