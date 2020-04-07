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
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DisableBundleConfiguredSettingsListenerTest extends TestCase
{
    public function testLoadCallbackExitsOnMissingLocalconfigParameter(): void
    {
        $container = $this->mockContainer();
        $container
            ->expects($this->once())
            ->method('hasParameter')
            ->with('contao.localconfig')
            ->willReturn(false)
        ;

        $container
            ->expects($this->never())
            ->method('getParameter')
            ->withAnyParameters()
        ;

        $listener = $this->createListener($container);
        $listener->onLoadCallback();
    }

    public function testLoadCallbackDisablesSettingsConfiguredByBundleConfiguration(): void
    {
        $container = $this->mockContainer();
        $container
            ->expects($this->once())
            ->method('hasParameter')
            ->with('contao.localconfig')
            ->willReturn(true)
        ;

        $container
            ->expects($this->once())
            ->method('getParameter')
            ->with('contao.localconfig')
            ->willReturn(
                [
                    'adminEmail' => 'admin@example.org',
                    'dateFormat' => 'd.M.Y',
                    'fooBar' => false,
                ]
            )
        ;

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

        $listener = $this->createListener($container);
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

    private function createListener(?ContainerInterface $container = null, ?TranslatorInterface $translator = null, array $adapters = []): DisableBundleConfiguredSettingsListener
    {
        $this->mockContaoFramework()->initialize();

        if (null === $container) {
            $container = $this->mockContainer();
        }

        if (null === $translator) {
            $translator = $this->mockTranslator();
        }

        $framework = $this->mockContaoFramework($adapters);
        $listener = new DisableBundleConfiguredSettingsListener($translator, $framework);
        $listener->setContainer($container);

        return $listener;
    }

    /** @return MockObject|TranslatorInterface */
    private function mockTranslator(): MockObject
    {
        return $this->createMock(TranslatorInterface::class);
    }

    /** @return MockObject|ContainerInterface */
    private function mockContainer(?array $localConfig = null): MockObject
    {
        $container = $this->createMock(Container::class);

        if (null !== $localConfig) {
            $container->setParameter('contao.localconfig', $localConfig);
        }

        return $container;
    }
}
