<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\EventListener\Widget;

use Contao\CoreBundle\DataContainer\RecordLabeler;
use Contao\CoreBundle\EventListener\Widget\RootPageDependentSelectListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\Image;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentSelectListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        $this->resetStaticProperties([System::class, Image::class]);

        parent::tearDown();
    }

    public function testDoesNotAddWizardWhenNoValuesSet(): void
    {
        $listener = new RootPageDependentSelectListener(
            $this->createStub(Connection::class),
            $this->createStub(UrlGeneratorInterface::class),
            $this->createStub(TranslatorInterface::class),
            $this->createStub(RecordLabeler::class),
        );

        $dataContainer = $this->createClassWithPropertiesStub(DataContainer::class);
        $dataContainer->value = serialize([]);

        $this->assertSame('', $listener->wizardCallback($dataContainer));
    }

    public function testAddWizardToSelectWhenModuleIsSet(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->exactly(3))
            ->method('trans')
            ->willReturn('title')
        ;

        System::setContainer($this->getContainerWithContaoConfiguration('/directory/project'));

        $listener = new RootPageDependentSelectListener(
            $this->createStub(Connection::class),
            $this->createStub(UrlGeneratorInterface::class),
            $translator,
            $this->createStub(RecordLabeler::class),
        );

        $dataContainer = $this->createClassWithPropertiesStub(DataContainer::class);
        $dataContainer->value = serialize([
            '1' => '10',
            '2' => '20',
            '3' => '30',
            '4' => '',
        ]);

        $this->assertCount(3, unserialize($listener->wizardCallback($dataContainer)));
    }

    public function testReturnsAllTypesOfModulesAsOption(): void
    {
        $this->populateGlobalsArray([]);

        $dataContainer = $this->createClassWithPropertiesMock(DataContainer::class);
        $dataContainer->table = 'tl_module';
        $dataContainer->field = 'field';

        $dataContainer
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 1])
        ;

        $connection = $this->mockConnection([], [
            ['id' => 10, 'name' => 'name-10', 'type' => 'foo'],
            ['id' => 20, 'name' => 'name-20', 'type' => 'bar'],
            ['id' => 30, 'name' => 'name-30', 'type' => 'baz'],
        ]);

        $listener = new RootPageDependentSelectListener(
            $connection,
            $this->createStub(UrlGeneratorInterface::class),
            $this->createTranslatorStub(),
            $this->createStub(RecordLabeler::class),
        );

        $this->assertSame(
            [
                'MSC.mw_modules' => [
                    10 => 'name-10',
                    20 => 'name-20',
                    30 => 'name-30',
                ],
            ],
            $listener->optionsCallback($dataContainer),
        );

        $this->unsetGlobalsArray();
    }

    public function testReturnsElementsAndModulesAsOption(): void
    {
        $this->populateGlobalsArray([]);

        $dataContainer = $this->createClassWithPropertiesMock(DataContainer::class);
        $dataContainer->table = 'tl_module';
        $dataContainer->field = 'field';

        $dataContainer
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 1])
        ;

        $connection = $this->mockConnection(
            [
                ['id' => 10, 'title' => 'title-10', 'type' => 'foo'],
                ['id' => 20, 'title' => 'title-20', 'type' => 'bar'],
                ['id' => 30, 'title' => 'title-30', 'type' => 'baz'],
            ],
            [
                ['id' => 10, 'name' => 'name-10', 'type' => 'foo'],
                ['id' => 20, 'name' => 'name-20', 'type' => 'bar'],
                ['id' => 30, 'name' => 'name-30', 'type' => 'baz'],
            ],
        );

        $listener = new RootPageDependentSelectListener(
            $connection,
            $this->createStub(UrlGeneratorInterface::class),
            $this->createTranslatorStub(),
            $this->createRecordLabelerStub(),
        );

        $this->assertSame(
            [
                'MSC.mw_elements' => [
                    'content-10' => 'title-10',
                    'content-20' => 'title-20',
                    'content-30' => 'title-30',
                ],
                'MSC.mw_modules' => [
                    10 => 'name-10',
                    20 => 'name-20',
                    30 => 'name-30',
                ],
            ],
            $listener->optionsCallback($dataContainer),
        );

        $this->unsetGlobalsArray();
    }

    public function testReturnsSelectedTypesOfModulesAsOption(): void
    {
        $this->populateGlobalsArray([
            'field' => [
                'eval' => [
                    'modules' => [
                        'foo',
                        'bar',
                    ],
                ],
            ],
        ]);

        $dataContainer = $this->createClassWithPropertiesMock(DataContainer::class);
        $dataContainer->table = 'tl_module';
        $dataContainer->field = 'field';

        $dataContainer
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 1])
        ;

        $connection = $this->mockConnection([], [
            ['id' => 10, 'name' => 'name-10', 'type' => 'foo'],
            ['id' => 20, 'name' => 'name-20', 'type' => 'bar'],
            ['id' => 30, 'name' => 'name-30', 'type' => 'baz'],
        ]);

        $listener = new RootPageDependentSelectListener(
            $connection,
            $this->createStub(UrlGeneratorInterface::class),
            $this->createTranslatorStub(),
            $this->createStub(RecordLabeler::class),
        );

        $this->assertSame(
            [
                'MSC.mw_modules' => [
                    10 => 'name-10',
                    20 => 'name-20',
                ],
            ],
            $listener->optionsCallback($dataContainer),
        );

        $this->unsetGlobalsArray();
    }

    private function populateGlobalsArray(array $data): void
    {
        $GLOBALS['TL_DCA']['tl_module']['fields'] = $data;
    }

    private function unsetGlobalsArray(): void
    {
        unset($GLOBALS['TL_DCA']['tl_module']['fields']);
    }

    private function mockConnection(array $elements, array $modules): Connection&MockObject
    {
        $contentResult = $this->createMock(Result::class);
        $contentResult
            ->expects($this->once())
            ->method('iterateAssociative')
            ->willReturn(new \ArrayIterator($elements))
        ;

        $moduleResult = $this->createMock(Result::class);
        $moduleResult
            ->expects($this->once())
            ->method('iterateAssociative')
            ->willReturn(new \ArrayIterator($modules))
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->exactly(2))
            ->method('executeQuery')
            ->willReturnMap([
                [
                    "SELECT * FROM tl_content WHERE ptable = 'tl_theme' AND pid = ?",
                    [1],
                    $contentResult,
                ],
                [
                    "SELECT m.id, m.name, m.type FROM tl_module m WHERE m.type != 'root_page_dependent_modules' AND m.pid = ? ORDER BY m.name",
                    [1],
                    $moduleResult,
                ],
            ])
        ;

        return $connection;
    }

    private function createTranslatorStub(): TranslatorInterface&Stub
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator
            ->method('trans')
            ->willReturnArgument(0)
        ;

        return $translator;
    }

    private function createRecordLabelerStub(): RecordLabeler&Stub
    {
        $recordLabeler = $this->createStub(RecordLabeler::class);
        $recordLabeler
            ->method('getLabel')
            ->willReturnCallback(static fn (string $id, array $element) => $element['title'])
        ;

        return $recordLabeler;
    }
}
