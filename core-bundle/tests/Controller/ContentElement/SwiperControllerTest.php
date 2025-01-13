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
use Contao\CoreBundle\Controller\ContentElement\SwiperController;
use Contao\CoreBundle\Fragment\Reference\ContentElementReference;

class SwiperControllerTest extends ContentElementTestCase
{
    public function testOutputsMarkup(): void
    {
        $text = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'text',
        ]);

        $image = $this->mockClassWithProperties(ContentModel::class, [
            'type' => 'image',
        ]);

        $response = $this->renderWithModelData(
            new SwiperController(),
            [
                'type' => 'swiper',
                'sliderDelay' => 1000,
                'sliderSpeed' => 300,
                'sliderStartSlide' => 0,
                'sliderContinuous' => true,
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

        $expectedJson = htmlspecialchars(json_encode([
            'speed' => 300,
            'offset' => 0,
            'loop' => true,
            'autoplay' => [
                'delay' => 1000,
                'pauseOnMouseEnter' => true,
            ],
        ]));

        // Replace }} with &#125;&#125; due to our insert tag filter
        $expectedJson = str_replace('}}', '&#125;&#125;', $expectedJson);

        $expectedOutput = <<<HTML
            <div class="content-swiper">
                <div class="swiper" data-settings="$expectedJson">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            text
                        </div>
                        <div class="swiper-slide">
                            image
                        </div>
                    </div>
                    <button type="button" class="swiper-button-prev"></button>
                    <button type="button" class="swiper-button-next"></button>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
        $this->assertArrayHasKey('swiper_css', $responseContextData['stylesheets']);
        $this->assertArrayHasKey('swiper_js', $responseContextData['body']);
    }
}
