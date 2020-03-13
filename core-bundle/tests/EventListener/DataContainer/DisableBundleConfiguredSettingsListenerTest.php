<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\EventListener\DataContainer\DisableBundleConfiguredSettingsListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\Image;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Contracts\Translation\TranslatorInterface;

class DisableBundleConfiguredSettingsListenerTest extends TestCase
{
    public function testLoadCallbackExitsOnMissingLocalconfigParameter(): void
    {
        $GLOBALS['TL_DCA']['tl_settings'] = [];
        $before = $GLOBALS['TL_DCA']['tl_settings'];

        $listener = $this->createListener();
        $listener->onLoadCallback();

        $this->assertSame($before, $GLOBALS['TL_DCA']['tl_settings']);
    }

    public function testLoadCallbackDisablesSettingsConfiguredByBundleConfiguration(): void
    {
        $GLOBALS['TL_DCA']['tl_settings']['fields'] = [
            'adminEmail' => [
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => true,
                    'rgxp' => 'friendly',
                    'decodeEntities' => true,
                    'tl_class' => 'w50',
                ],
            ],
            'dateFormat' => [
                'inputType' => 'text',
                'eval' => [
                    'mandatory' => true,
                    'helpwizard' => true,
                    'decodeEntities' => true,
                    'tl_class' => 'w50',
                ],
                'explanation' => 'dateFormat',
            ],
        ];

        $listener = $this->createListener(
            [
                'adminEmail' => 'admin@example.org',
                'dateFormat' => 'd.M.Y',
                'fooBar' => false,
            ]
        );
        $listener->onLoadCallback();

        $this->assertSame(
            [
                'adminEmail' => [
                    'inputType' => 'text',
                    'eval' => [
                        'mandatory' => true,
                        'rgxp' => 'friendly',
                        'decodeEntities' => true,
                        'tl_class' => 'w50',
                        'disabled' => true,
                        'helpwizard' => false,
                    ],
                    'xlabel' => [[DisableBundleConfiguredSettingsListener::class, 'renderHelpIcon']],
                ],
                'dateFormat' => [
                    'inputType' => 'text',
                    'eval' => [
                        'mandatory' => true,
                        'helpwizard' => false,
                        'decodeEntities' => true,
                        'tl_class' => 'w50',
                        'disabled' => true,
                    ],
                    'explanation' => 'dateFormat',
                    'xlabel' => [[DisableBundleConfiguredSettingsListener::class, 'renderHelpIcon']],
                ],
            ],
            $GLOBALS['TL_DCA']['tl_settings']['fields']
        );
    }

    public function testRenderHelpIcon(): void
    {
        $translator = $this->mockTranslator();
        $translator
            ->expects($this->exactly(2))
            ->method('trans')
        ;

        $imageAdapter = $this->mockAdapter(['getHtml']);
        $imageAdapter
            ->expects($this->once())
            ->method('getHtml')
            ->willReturn('<img src="system/themes/icons/important.svg" alt="Alt text" title="title">')
        ;

        $listener = $this->createListener(null, $translator, [Image::class => $imageAdapter]);

        $this->assertSame(
            '<img src="system/themes/icons/important.svg" alt="Alt text" title="title">',
            $listener->renderHelpIcon()
        );
    }

    private function createListener(array $localConfig = null, ?TranslatorInterface $translator = null, array $adapters = []): DisableBundleConfiguredSettingsListener
    {
        $this->mockContaoFramework()->initialize();

        if (null === $translator) {
            $translator = $this->mockTranslator();
        }

        $framework = $this->mockContaoFramework($adapters);

        return new DisableBundleConfiguredSettingsListener($translator, $framework, $localConfig ?: []);
    }

    /** @return MockObject|TranslatorInterface */
    private function mockTranslator(): MockObject
    {
        return $this->createMock(TranslatorInterface::class);
    }
}
