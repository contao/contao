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

use Contao\ContentModel;
use Contao\Controller;
use Contao\CoreBundle\EventListener\DataContainer\OverwriteFormSettingsListener;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\FormModel;
use Contao\System;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\MockObject\MockObject;

class OverwriteFormSettingsListenerTest extends TestCase
{
    public function testAnnotatedHook(): void
    {
        $listener = new OverwriteFormSettingsListener($this->mockContaoFramework());
        $annotationReader = new AnnotationReader();

        /** @var Hook $annotation */
        $annotation = $annotationReader->getMethodAnnotation(new \ReflectionMethod($listener, 'addOverwritableFields'), Hook::class);

        $this->assertSame('loadDataContainer', $annotation->value);
    }

    /**
     * @dataProvider getTables
     */
    public function testCopiesOverwritableFields(string $table): void
    {
        $GLOBALS['TL_DCA']['tl_form'] = [
            'fields' => [
                'recipient' => [
                    'eval' => ['formOverwritable' => true, 'mandatory' => true],
                ],
                'alias' => [],
            ],
        ];

        $GLOBALS['TL_DCA'][$table] = [
            '__selector__' => ['overwriteFormSettings'],
            'subpalettes' => ['overwriteFormSettings' => ''],
            'fields' => [],
        ];

        $expectedTable = [
            '__selector__' => ['overwriteFormSettings'],
            'subpalettes' => ['overwriteFormSettings' => 'form_recipient'],
            'fields' => [
                'form_recipient' => [
                    'eval' => ['formOverwritable' => true, 'mandatory' => false],
                    'load_callback' => [
                        ['contao.listener.data_container.overwrite_form_settings', 'getPlaceholderFromForm'],
                    ],
                ],
            ],
        ];

        $controllerAdapter = $this->mockAdapter(['loadDataContainer']);
        $controllerAdapter
            ->expects($this->once())
            ->method('loadDataContainer')
            ->with('tl_form')
        ;

        $systemAdapter = $this->mockAdapter(['loadLanguageFile']);
        $systemAdapter
            ->expects($this->once())
            ->method('loadLanguageFile')
            ->with('tl_form')
        ;

        $framework = $this->mockContaoFramework([
            Controller::class => $controllerAdapter,
            System::class => $systemAdapter,
        ]);

        $listener = new OverwriteFormSettingsListener($framework);
        $listener->addOverwritableFields($table);

        $this->assertSame($expectedTable, $GLOBALS['TL_DCA'][$table]);
    }

    /**
     * @dataProvider getTables
     */
    public function testGetPlaceholderFromForm(string $table): void
    {
        $GLOBALS['TL_DCA'][$table]['fields']['form_recipient']['eval']['placeholder'] = '';

        $recipient = 'foo@bar.org';
        $contentModel = $this->mockClassWithProperties(ContentModel::class, ['form' => 1, 'form_recipient' => '']);
        $formModel = $this->mockClassWithProperties(FormModel::class, ['recipient' => $recipient]);

        $formModelAdapter = $this->mockAdapter(['findById']);
        $formModelAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($formModel)
        ;

        $framework = $this->mockContaoFramework([
            FormModel::class => $formModelAdapter,
        ]);

        /** @var MockObject&DataContainer $dc */
        $dc = $this->mockClassWithProperties(DataContainer::class, [
            'table' => $table,
            'field' => 'form_recipient',
            'activeRecord' => $contentModel,
        ]);

        $listener = new OverwriteFormSettingsListener($framework);
        $listener->getPlaceholderFromForm(null, $dc);

        $this->assertArrayHasKey('placeholder', $GLOBALS['TL_DCA'][$table]['fields']['form_recipient']['eval']);
        $this->assertSame($recipient, $GLOBALS['TL_DCA'][$table]['fields']['form_recipient']['eval']['placeholder']);

        unset($GLOBALS['TL_DCA']);
    }

    public function getTables(): \Generator
    {
        yield ['tl_content'];
        yield ['tl_module'];
    }
}
