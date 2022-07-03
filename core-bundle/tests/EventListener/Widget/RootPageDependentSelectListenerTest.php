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

use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\EventListener\Widget\RootPageDependentSelectListener;
use Contao\CoreBundle\Tests\TestCase;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class RootPageDependentSelectListenerTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    public function testDoesNotAddWizardWhenNoValuesSet(): void
    {
        $listener = new RootPageDependentSelectListener(
            $this->createMock(Connection::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $dataContainer = $this->mockClassWithProperties(DataContainer::class);
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

        $csrfTokenManager = $this->createMock(ContaoCsrfTokenManager::class);
        $csrfTokenManager
            ->expects($this->exactly(3))
            ->method('getDefaultTokenValue')
        ;

        System::setContainer($this->getContainerWithContaoConfiguration('/directory/project'));

        $listener = new RootPageDependentSelectListener(
            $this->createMock(Connection::class),
            $this->createMock(UrlGeneratorInterface::class),
            $translator,
            $csrfTokenManager,
        );

        $dataContainer = $this->mockClassWithProperties(DataContainer::class);
        $dataContainer->value = serialize([
            '1' => '10',
            '2' => '20',
            '3' => '30',
            '4' => '',
        ]);

        $this->assertCount(3, unserialize($listener->wizardCallback($dataContainer)));
    }

    public function testDoesNotSaveUnserializableData(): void
    {
        $dataContainer = $this->mockClassWithProperties(DataContainer::class);

        $listener = new RootPageDependentSelectListener(
            $this->createMock(Connection::class),
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $this->assertSame('foobar', $listener->saveCallback('foobar', $dataContainer));
    }

    public function testSavesValuesRelatedToRootPage(): void
    {
        $dataContainer = $this->mockClassWithProperties(DataContainer::class);
        $connection = $this->mockGetRootPages();

        $listener = new RootPageDependentSelectListener(
            $connection,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class),
        );

        $this->assertSame(
            serialize([
                1 => 10,
                2 => 20,
                3 => 30,
            ]),
            $listener->saveCallback(serialize([10, 20, 30]), $dataContainer)
        );
    }

    public function testReturnsAllTypesOfModulesAsOption(): void
    {
        $this->populateGlobalsArray([]);

        $dataContainer = $this->mockClassWithProperties(DataContainer::class);
        $dataContainer->table = 'tl_module';
        $dataContainer->field = 'field';

        $dataContainer
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 1])
        ;

        $connection = $this->mockGetModules();

        $listener = new RootPageDependentSelectListener(
            $connection,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $this->assertSame(
            [
                10 => 'name-10',
                20 => 'name-20',
                30 => 'name-30',
            ],
            $listener->optionsCallback($dataContainer)
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

        $dataContainer = $this->mockClassWithProperties(DataContainer::class);
        $dataContainer->table = 'tl_module';
        $dataContainer->field = 'field';

        $dataContainer
            ->expects($this->once())
            ->method('getCurrentRecord')
            ->willReturn(['pid' => 1])
        ;

        $connection = $this->mockGetModules();

        $listener = new RootPageDependentSelectListener(
            $connection,
            $this->createMock(UrlGeneratorInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(ContaoCsrfTokenManager::class)
        );

        $this->assertSame(
            [
                10 => 'name-10',
                20 => 'name-20',
            ],
            $listener->optionsCallback($dataContainer)
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

    private function mockGetRootPages(): Connection
    {
        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('iterateAssociative')
            ->willReturn(new \ArrayIterator([
                ['id' => 1, 'title' => 'title-1', 'language' => 'language-1'],
                ['id' => 2, 'title' => 'title-2', 'language' => 'language-2'],
                ['id' => 3, 'title' => 'title-3', 'language' => 'language-3'],
            ]))
        ;

        $statement = $this->createMock(Statement::class);
        $statement
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('prepare')
            ->willReturn($statement)
        ;

        return $connection;
    }

    private function mockGetModules(): Connection
    {
        $result = $this->createMock(Result::class);
        $result
            ->expects($this->once())
            ->method('iterateAssociative')
            ->willReturn(new \ArrayIterator([
                ['id' => 10, 'name' => 'name-10', 'type' => 'foo'],
                ['id' => 20, 'name' => 'name-20', 'type' => 'bar'],
                ['id' => 30, 'name' => 'name-30', 'type' => 'baz'],
            ]))
        ;

        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('executeQuery')
            ->willReturn($result)
        ;

        return $connection;
    }
}
