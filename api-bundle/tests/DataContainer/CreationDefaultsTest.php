<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\ApiBundle\Tests\DataContainer;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Contao\ApiBundle\DataContainer\DataContainerRecordMapper;
use Contao\ApiBundle\DataContainer\DataContainerRelationDefinitionFactory;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\DataContainer\DataContainerWidgetFactory;
use Contao\ApiBundle\DataContainer\TableDataContainerRecords;
use Contao\ApiBundle\Dto\DataContainerRecord;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CheckBox;
use Contao\Controller;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\DcaRequestSwitcher;
use Contao\CoreBundle\DataContainer\DcaUrlAnalyzer;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\DC_Table;
use Contao\DcaLoader;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Contao\TextField;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\LocaleSwitcher;

/**
 * The widget stores what the create submits for an unsent field: an empty array becomes
 * "a:0:{}" and "0" becomes 0, where the back end keeps "" and NULL. A GET cannot show
 * the difference, because it maps "a:0:{}" and "" to [] and NULL to 0.
 */
final class CreationDefaultsTest extends ContaoTestCase
{
    private RequestStack $requestStack;

    protected function setUp(): void
    {
        parent::setUp();

        System::setContainer($this->getContainerWithContaoConfiguration());

        $GLOBALS['BE_FFL']['text'] = TextField::class;
        $GLOBALS['BE_FFL']['checkbox'] = CheckBox::class;

        // The fields as in core-bundle/contao/dca/tl_image_size.php
        $GLOBALS['TL_DCA']['tl_image_size']['config']['dataContainer'] = DC_Table::class;

        $GLOBALS['TL_DCA']['tl_image_size']['fields'] = [
            'name' => ['inputType' => 'text', 'eval' => ['mandatory' => true], 'sql' => ['type' => 'string', 'length' => 64, 'default' => '']],
            'height' => ['inputType' => 'text', 'eval' => ['rgxp' => 'natural'], 'sql' => ['type' => 'integer', 'notnull' => false, 'unsigned' => true]],
            'formats' => ['inputType' => 'checkbox', 'options' => ['png:webp,png', 'jpg:webp,jpg'], 'eval' => ['multiple' => true], 'sql' => ['type' => 'string', 'length' => 1024, 'default' => '']],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA'], $GLOBALS['BE_FFL']);

        $this->resetStaticProperties([System::class]);

        parent::tearDown();
    }

    #[DataProvider('provideUnsentFields')]
    public function testSubmitsUnsentFieldsAsTheyAreStored(string $field, string|null $stored): void
    {
        $submitted = null;

        $dc = $this->createMock(DC_Table::class);
        $dc
            ->method('__get')
            ->willReturnMap([['table', 'tl_image_size']])
        ;
        $dc
            ->method('getPalette')
            ->willReturn('{title_legend},name,height;{expert_legend:hide},formats')
        ;
        $dc
            ->method('getCurrentRecord')
            ->willReturn(['id' => 6, 'tstamp' => 0, 'name' => '', 'height' => null, 'formats' => ''])
        ;
        $dc
            ->method('create')
            ->willThrowException(new ResponseException(new RedirectResponse('/contao?act=edit&id=6')))
        ;
        $dc
            ->expects($this->once())
            ->method('edit')
            ->willReturnCallback(
                function () use (&$submitted): never {
                    $submitted = $this->requestStack->getCurrentRequest()->request->all();

                    throw new ResponseException(new RedirectResponse('/contao'));
                },
            )
        ;

        $this->createRecords($dc)->create(new DataContainerRecord('tl_image_size', ['name' => 'API size']));

        $this->assertSame('API size', $submitted['name'] ?? null);

        // Either the field is left out or it is submitted as the back end form would
        // submit the stored value
        $this->assertContains($submitted[$field] ?? null, [null, $stored ?? ''], \sprintf('Field "%s" was submitted as %s.', $field, json_encode($submitted[$field] ?? null)));
    }

    public static function provideUnsentFields(): iterable
    {
        yield 'empty multiple selection (stored as a:0:{})' => ['formats', ''];
        yield 'nullable integer (stored as 0)' => ['height', null];
    }

    private function createRecords(DC_Table $dc): TableDataContainerRecords
    {
        $system = $this->createAdapterStub(['loadLanguageFile']);
        $controller = $this->createAdapterStub(['loadDataContainer']);
        $loader = $this->createAdapterStub(['switchToCurrentRequest']);

        $framework = $this->createContaoFrameworkStub([Controller::class => $controller, DcaLoader::class => $loader, System::class => $system]);
        $framework
            ->method('createInstance')
            ->willReturn($dc)
        ;

        $converters = new WidgetConverterRegistry([new CoreWidgetConverter(new DateValueFormatter($this->createStub(ContaoFramework::class)))]);
        $metadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $relationResolver = new DataContainerRelationResolver($this->createStub(Connection::class), $metadataFactory, $this->createStub(RouterInterface::class), $this->createStub(DcaHierarchy::class));
        $relationDefinitionFactory = new DataContainerRelationDefinitionFactory(new ForeignKeyParser($this->createStub(Connection::class)), $converters, $metadataFactory);

        $localeSwitcher = $this->createStub(LocaleSwitcher::class);
        $localeSwitcher
            ->method('runWithLocale')
            ->willReturnCallback(static fn (string $locale, callable $callback): mixed => $callback($locale))
        ;

        $mapper = new DataContainerRecordMapper(new DataContainerSchemaFactory($framework, $converters, $relationResolver, $localeSwitcher, $relationDefinitionFactory, new DataContainerWidgetFactory()), $converters, $relationResolver, $relationDefinitionFactory);

        $connection = $this->createStub(Connection::class);
        $connection
            ->method('transactional')
            ->willReturnCallback(static fn ($callback) => $callback())
        ;

        $stack = $this->requestStack = new RequestStack();
        $stack->push(Request::create('/contao', 'POST'));

        $analyzer = $this->createStub(DcaUrlAnalyzer::class);
        $analyzer
            ->method('getEditUrl')
            ->willReturn('/contao?do=themes')
        ;

        $router = $this->createStub(UrlGeneratorInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn ($route, $parameters) => '/contao?'.http_build_query($parameters))
        ;

        return new TableDataContainerRecords($mapper, $connection, $framework, $stack, $analyzer, $router, new DcaRequestSwitcher($framework, $stack));
    }
}
