<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

namespace Contao\CoreBundle\Tests\Api\Widget;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Contao\ApiBundle\DataContainer\DataContainerRelationDefinitionFactory;
use Contao\ApiBundle\DataContainer\DataContainerRelationResolver;
use Contao\ApiBundle\Dto\VirtualFilesystemItem;
use Contao\ApiBundle\Relation\RelationReference;
use Contao\ApiBundle\Relation\RelationResolver;
use Contao\ApiBundle\Relation\RelationResolverInterface;
use Contao\ApiBundle\Relation\VirtualFilesystemRelationDefinition;
use Contao\ApiBundle\Relation\VirtualFilesystemRelationResolver;
use Contao\ApiBundle\Schema\DataContainerSchemaFactory;
use Contao\ApiBundle\Widget\RelationAwareWidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterInterface;
use Contao\ApiBundle\Widget\WidgetConverterRegistry;
use Contao\CoreBundle\Api\Widget\CoreWidgetConverter;
use Contao\CoreBundle\Api\Widget\RowWizardConverter;
use Contao\CoreBundle\DataContainer\DcaHierarchy;
use Contao\CoreBundle\DataContainer\ForeignKeyParser;
use Contao\CoreBundle\Filesystem\ExtraMetadata;
use Contao\CoreBundle\Filesystem\FilesystemItem;
use Contao\CoreBundle\Filesystem\VirtualFilesystemInterface;
use Contao\CoreBundle\Filesystem\VirtualFilesystemRegistry;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Widget\DateValueFormatter;
use Contao\FileTree;
use Contao\Password;
use Contao\RowWizard;
use Contao\StringUtil;
use Contao\TextField;
use Doctrine\DBAL\Connection;
use Opis\JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Symfony\Component\Uid\Uuid;

class RowWizardConverterTest extends TestCase
{
    private ResourceMetadataCollectionFactoryInterface $relationMetadataFactory;

    private array|null $widgets;

    protected function setUp(): void
    {
        $this->widgets = $GLOBALS['BE_FFL'] ?? null;
        $GLOBALS['BE_FFL'] = ['rows' => RowWizard::class, 'text' => TextField::class, 'file' => FileTree::class, 'password' => Password::class];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_FFL']);

        if (null !== $this->widgets) {
            $GLOBALS['BE_FFL'] = $this->widgets;
        }
    }

    public function testConvertsCellsAndPreparesRowMarkers(): void
    {
        $config = ['inputType' => 'rows', 'eval' => ['actions' => ['enable']], 'fields' => [
            'id' => ['inputType' => 'text', 'eval' => ['maxlength' => 20]],
            'file' => ['inputType' => 'file'],
        ]];

        $converter = $this->createConverter();
        $schema = $converter->getSchema($config, ['type' => 'string']);

        $this->assertTrue($converter->supports($config));
        $this->assertSame(['type' => 'string', 'maxLength' => 20], $schema['items']['properties']['id']);

        $uuid = '12345678-1234-1234-1234-123456789abc';
        $rows = $converter->convertToApiValue(serialize([4 => ['id' => 'test', 'file' => StringUtil::uuidToBin($uuid), 'enable' => '1']]), $config, $schema);

        $this->assertSame('[{"id":"test","file":"'.$uuid.'","enable":true}]', json_encode($rows, JSON_THROW_ON_ERROR));

        $this->assertSame(
            [
                '_rows' => ['1'],
                ['id' => 'test', 'file' => $uuid, 'enable' => '1'],
            ],
            $converter->convertToFormValue($rows, $config, $schema),
        );

        $this->assertSame(['_rows' => []], $converter->convertToFormValue([], $config, $schema));
        $this->assertSame([], $converter->convertToApiValue('', $config, $schema));
    }

    public function testDelegatesToThirdPartyConverters(): void
    {
        $field = ['inputType' => 'custom'];

        $child = $this->createMock(WidgetConverterInterface::class);
        $child
            ->method('supports')
            ->willReturnCallback(static fn (array $config): bool => 'custom' === ($config['inputType'] ?? null))
        ;

        $child
            ->expects($this->once())
            ->method('getSchema')
            ->with($field, ['type' => 'string'])
            ->willReturn(['type' => 'integer'])
        ;

        $child
            ->expects($this->once())
            ->method('convertToApiValue')
            ->with('stored', $field, ['type' => 'integer'])
            ->willReturn(42)
        ;

        $child
            ->expects($this->once())
            ->method('convertToFormValue')
            ->with(42, $field, ['type' => 'integer'])
            ->willReturn('submitted')
        ;

        $converter = $this->createConverter($child);
        $config = ['inputType' => 'rows', 'eval' => ['fields' => ['custom' => $field]]];

        $this->assertTrue($converter->supports($config));

        $schema = $converter->getSchema($config, []);
        $rows = $converter->convertToApiValue([['custom' => 'stored']], $config, $schema);

        $this->assertSame(42, $rows[0]->custom);
        $this->assertSame(['_rows' => ['1'], ['custom' => 'submitted']], $converter->convertToFormValue($rows, $config, $schema));
    }

    public function testSupportsNestedRowsAndRejectsUnsupportedChildren(): void
    {
        $converter = $this->createConverter();
        $nested = ['inputType' => 'rows', 'fields' => ['title' => ['inputType' => 'text']]];
        $config = ['inputType' => 'rows', 'fields' => ['children' => $nested]];

        $this->assertTrue($converter->supports($config));

        $schema = $converter->getSchema($config, []);
        $rows = $converter->convertToApiValue([['children' => [['title' => 'Nested']]]], $config, $schema);

        $this->assertSame(['_rows' => ['1'], ['children' => ['_rows' => ['1'], ['title' => 'Nested']]]], $converter->convertToFormValue($rows, $config, $schema));

        $config['fields']['unsupported'] = ['inputType' => 'unknown'];
        $this->assertFalse($converter->supports($config));
        $this->assertFalse($converter->supports(['inputType' => 'text']));
    }

    public function testRespectsChildAccessAndSchemaOverrides(): void
    {
        $config = ['inputType' => 'rows', 'fields' => [
            'secret' => ['inputType' => 'password'],
            'locked' => ['inputType' => 'text', 'eval' => ['readonly' => true], 'api' => ['schema' => ['enum' => ['test']]]],
        ]];

        $converter = $this->createConverter();
        $schema = $converter->getSchema($config, []);

        $this->assertTrue($schema['readOnly']);
        $this->assertSame(['test'], $schema['items']['properties']['locked']['enum']);

        $rows = $converter->convertToApiValue([['secret' => 'hash', 'locked' => 'test']], $config, $schema);
        $this->assertSame('{"locked":"test"}', json_encode($rows[0], JSON_THROW_ON_ERROR));
    }

    public function testTopLevelFieldsTakePrecedenceAndJsonStorageIsDecoded(): void
    {
        $converter = $this->createConverter();
        $config = ['inputType' => 'rows', 'sql' => ['type' => 'json'], 'fields' => ['title' => ['inputType' => 'text']], 'eval' => ['fields' => ['ignored' => ['inputType' => 'unknown']]]];
        $this->assertTrue($converter->supports($config));

        $schema = $converter->getSchema($config, []);
        $this->assertSame(['title'], array_keys($schema['items']['properties']));

        $rows = $converter->convertToApiValue('[{"title":"JSON"}]', $config, $schema);
        $this->assertSame('JSON', $rows[0]->title);

        $validator = new Validator();
        $this->assertTrue($validator->validate($rows, json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR))->isValid());
    }

    public function testConvertsDisabledRowsWithoutDroppingThem(): void
    {
        $config = ['inputType' => 'rows', 'eval' => ['actions' => ['enable']], 'fields' => ['title' => ['inputType' => 'text']]];

        $converter = $this->createConverter();
        $schema = $converter->getSchema($config, []);
        $rows = $converter->convertToApiValue([['title' => 'Disabled', 'enable' => '']], $config, $schema);

        $this->assertFalse($rows[0]->enable);
        $this->assertSame(['_rows' => ['1'], ['title' => 'Disabled', 'enable' => '']], $converter->convertToFormValue($rows, $config, $schema));
    }

    public function testConvertsFileRelationsInSingleMultipleAndNestedCells(): void
    {
        $converter = $this->createConverter(files: true);
        $single = ['inputType' => 'file'];
        $multiple = ['inputType' => 'file', 'eval' => ['multiple' => true, 'binary' => false]];

        $config = ['inputType' => 'rows', 'fields' => [
            'single' => $single,
            'multiple' => $multiple,
            'nested' => ['inputType' => 'rows', 'fields' => ['file' => $single]],
        ]];

        $uuid = '12345678-1234-1234-8234-123456789abc';
        $schema = $converter->getSchema($config, []);

        $this->assertSame(['object', 'null'], $schema['items']['properties']['single']['type']);
        $this->assertArrayNotHasKey('format', $schema['items']['properties']['single']);
        $this->assertSame(['object', 'null'], $schema['items']['properties']['multiple']['items']['type']);

        $rows = $converter->convertToApiValue(serialize([[
            'single' => StringUtil::uuidToBin($uuid),
            'multiple' => [$uuid, $uuid],
            'nested' => [['file' => StringUtil::uuidToBin($uuid)]],
        ]]), $config, $schema);

        $this->assertEquals(new RelationReference($uuid, '/contao/api/files/by-uuid/'.$uuid), $rows[0]->single);
        $this->assertEquals([new RelationReference($uuid, '/contao/api/files/by-uuid/'.$uuid), new RelationReference($uuid, '/contao/api/files/by-uuid/'.$uuid)], $rows[0]->multiple);
        $this->assertEquals(new RelationReference($uuid, '/contao/api/files/by-uuid/'.$uuid), $rows[0]->nested[0]->file);
        $this->assertTrue(new Validator()->validate($rows, json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR))->isValid());

        $this->assertSame(
            [
                '_rows' => ['1'],
                ['single' => $uuid, 'multiple' => $uuid.','.$uuid, 'nested' => ['_rows' => ['1'], ['file' => $uuid]]],
            ],
            $converter->convertToFormValue(json_decode(json_encode($rows, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR), $config, $schema),
        );
    }

    public function testSupportsCustomWidgetsStoringPathsWithoutUuids(): void
    {
        $custom = $this->createStub(RelationAwareWidgetConverterInterface::class);
        $custom
            ->method('supports')
            ->willReturnCallback(static fn (array $config): bool => 'pathPicker' === ($config['inputType'] ?? null))
        ;

        $custom
            ->method('getRelation')
            ->willReturn(VirtualFilesystemRelationDefinition::forPath('files'))
        ;

        $custom
            ->method('getSchema')
            ->willReturn(['type' => 'string'])
        ;

        $custom
            ->method('convertToApiValue')
            ->willReturnArgument(0)
        ;

        $custom
            ->method('convertToFormValue')
            ->willReturnArgument(0)
        ;
        $converter = $this->createConverter($custom, true);
        $config = ['inputType' => 'rows', 'fields' => ['file' => ['inputType' => 'pathPicker']]];
        $schema = $converter->getSchema($config, []);

        $this->assertTrue($converter->supports($config));
        $this->assertSame(['object', 'null'], $schema['items']['properties']['file']['type']);

        foreach (['documents/example.txt', '12345678-1234-1234-8234-123456789abc'] as $path) {
            $rows = $converter->convertToApiValue(serialize([['file' => $path]]), $config, $schema);
            $this->assertEquals(new RelationReference($path, '/contao/api/files/by-path/'.$path), $rows[0]->file);
            $this->assertTrue(new Validator()->validate($rows, json_decode(json_encode($schema, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR))->isValid());
            $this->assertSame(
                ['_rows' => ['1'], ['file' => $path]],
                $converter->convertToFormValue(json_decode(json_encode($rows, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR), $config, $schema),
            );
        }
    }

    private function createConverter(WidgetConverterInterface|null $custom = null, bool $files = false): RowWizardConverter
    {
        $framework = $this->createStub(ContaoFramework::class);
        $converters = new \ArrayObject($custom ? [$custom] : []);
        $converters[] = new CoreWidgetConverter(new DateValueFormatter($framework));
        $registry = new WidgetConverterRegistry($converters);

        $resolver = $this->createRelationResolver($files);
        $definitions = $this->createRelationDefinitionFactory($registry);
        $converter = new RowWizardConverter($registry, new DataContainerSchemaFactory($framework, $registry, $resolver, $this->createLocaleSwitcher(), $definitions), $resolver, $definitions);
        $converters[] = $converter;

        return $converter;
    }

    private function createRelationResolver(bool $files): RelationResolverInterface
    {
        $metadataFactory = $this->createStub(ResourceMetadataCollectionFactoryInterface::class);
        $metadataFactory
            ->method('create')
            ->willReturnCallback(static fn (string $class): ResourceMetadataCollection => new ResourceMetadataCollection($class, $files && VirtualFilesystemItem::class === $class ? [new ApiResource(operations: [
                new Get(name: 'files_get_by_uuid', uriVariables: ['uuid' => new Link(identifiers: ['metadata[uuid]'])], extraProperties: ['contao' => ['filesystem' => 'files']]),
                new Get(name: 'files_get_by_path', uriVariables: ['path' => new Link(identifiers: ['path'])], extraProperties: ['contao' => ['filesystem' => 'files']]),
            ])] : []))
        ;

        $this->relationMetadataFactory = $metadataFactory;

        $router = $this->createStub(RouterInterface::class);
        $router
            ->method('generate')
            ->willReturnCallback(static fn (string $route, array $parameters): string => 'files_get_by_uuid' === $route ? '/contao/api/files/by-uuid/'.$parameters['uuid'] : '/contao/api/files/by-path/'.$parameters['path'])
        ;

        $router
            ->method('match')
            ->willReturnCallback(static fn (string $path): array => str_starts_with($path, '/contao/api/files/by-uuid/') ? ['_route' => 'files_get_by_uuid', 'uuid' => substr($path, \strlen('/contao/api/files/by-uuid/'))] : ['_route' => 'files_get_by_path', 'path' => substr($path, \strlen('/contao/api/files/by-path/'))])
        ;

        return new RelationResolver([
            new DataContainerRelationResolver($this->createStub(Connection::class), $metadataFactory, $router, $this->createStub(DcaHierarchy::class)),
            new VirtualFilesystemRelationResolver($metadataFactory, $router, new VirtualFilesystemRegistry(new ServiceLocator(['files' => $this->createFilesStorage(...)]), ['files' => []])),
        ]);
    }

    private function createFilesStorage(): VirtualFilesystemInterface
    {
        $storage = $this->createStub(VirtualFilesystemInterface::class);
        $storage
            ->method('get')
            ->willReturnCallback(static fn (Uuid|string $location): FilesystemItem => new FilesystemItem(
                true,
                $location instanceof Uuid ? 'documents/example.txt' : $location,
                extraMetadata: new ExtraMetadata($location instanceof Uuid ? ['uuid' => $location] : []),
            ))
        ;

        return $storage;
    }

    private function createLocaleSwitcher(): LocaleSwitcher
    {
        $localeSwitcher = $this->createStub(LocaleSwitcher::class);
        $localeSwitcher
            ->method('runWithLocale')
            ->willReturnCallback(static fn (string $locale, callable $callback): mixed => $callback($locale))
        ;

        return $localeSwitcher;
    }

    private function createRelationDefinitionFactory(WidgetConverterRegistry $converters): DataContainerRelationDefinitionFactory
    {
        return new DataContainerRelationDefinitionFactory(new ForeignKeyParser($this->createStub(Connection::class)), $converters, $this->relationMetadataFactory);
    }
}
