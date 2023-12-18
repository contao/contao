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

use Contao\CoreBundle\Controller\ContentElement\SliderController;
use Contao\CoreBundle\Controller\ContentElement\TextController;
use Symfony\Component\HttpFoundation\Request;

class SliderControllerTest extends ContentElementTestCase
{
    public function testOutputsMarkup(): void
    {
        $nested = $this->renderWithModelData(
            new TextController($this->getDefaultStudio()),
            [
                'type' => 'text',
                'text' => '<p>Foo.</p>',
            ],
        );

        $request = new Request();
        $request->attributes->set('nestedFragments', [$nested]);

        $response = $this->renderWithModelData(
            new SliderController(),
            [
                'type' => 'slider',
                'sliderDelay' => 0,
                'sliderSpeed' => 300,
                'sliderStartSlide' => 0,
                'sliderContinuous' => true,
            ],
            null,
            false,
            $responseContextData,
            null,
            $request,
        );

        $expectedOutput = <<<'HTML'
            <div class="content-slider">
                <div class="swiper" data-delay="0" data-speed="300" data-offset="0" data-loop="1">
                    <div class="swiper-wrapper">
                        <div class="swiper-slide">
                            <div class="content-text">
                                <div class="rte">
                                    <p>Foo.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            HTML;

        $this->assertSameHtml($expectedOutput, $response->getContent());
    }
}
