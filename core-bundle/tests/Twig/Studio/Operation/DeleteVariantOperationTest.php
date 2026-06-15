<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Twig\Loader\ContaoFilesystemLoader;
use Contao\CoreBundle\Twig\Studio\Operation\AbstractDeleteVariantOperation;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

class DeleteVariantOperationTest extends AbstractOperationTestCase
{
    #[DataProvider('provideContextsAndIfAllowedToExecute')]
    public function testCanExecute(OperationContext $context, bool $canExecute, array $chain): void
    {
        $filesystemLoader = $this->createStub(ContaoFilesystemLoader::class);
        $filesystemLoader
            ->method('getInheritanceChains')
            ->willReturn([$context->getIdentifier() => $chain])
        ;

        $this->assertSame(
            $canExecute,
            $this->getDeleteVariantOperation(null, $filesystemLoader)->canExecute($context),
        );
    }

    public static function provideContextsAndIfAllowedToExecute(): iterable
    {
        yield 'arbitrary identifier' => [
            static::getOperationContext('bar/foo'),
            false,
            [
                '/templates/bar/foo.html.twig' => '@Contao_Global/bar/foo.html.twig',
                '/vendor/contao/core-bundle/contao/templates/bar/foo.html.twig' => '@Contao_ContaoCoreBundle/bar/foo.html.twig',
            ],
        ];

        yield 'identifier matching the prefix' => [
            static::getOperationContext('prefix/foo'),
            false,
            [
                '/templates/prefix/foo.html.twig' => '@Contao_Global/prefix/foo.html.twig',
                '/vendor/contao/core-bundle/contao/templates/prefix/foo.html.twig' => '@Contao_ContaoCoreBundle/prefix/foo.html.twig',
            ],
        ];

        yield 'matching variant identifier' => [
            static::getOperationContext('prefix/foo/my_variant'),
            true,
            [
                '/templates/prefix/foo/my_variant.html.twig' => '@Contao_Global/prefix/foo/my_variant.html.twig',
                '/vendor/contao/core-bundle/contao/templates/prefix/foo/my_variant.html.twig' => '@Contao_ContaoCoreBundle/prefix/foo/my_variant.html.twig',
            ],
        ];

        yield 'matching nested variant identifier' => [
            static::getOperationContext('prefix/foo/bar/my_variant'),
            true,
            [
                '/templates/prefix/foo/bar/my_variant.html.twig' => '@Contao_Global/prefix/foo/bar/my_variant.html.twig',
                '/vendor/contao/core-bundle/contao/templates/prefix/foo/bar/my_variant.html.twig' => '@Contao_ContaoCoreBundle/prefix/foo/bar/my_variant.html.twig',
            ],
        ];

        yield 'arbitrary identifier in theme context' => [
            static::getOperationContext('bar/foo', 'theme'),
            false,
            [
                '/templates/bar/foo.html.twig' => '@Contao_Global/bar/foo.html.twig',
                '/vendor/contao/core-bundle/contao/templates/bar/foo.html.twig' => '@Contao_ContaoCoreBundle/bar/foo.html.twig',
            ],
        ];

        yield 'identifier matching the prefix in theme context' => [
            static::getOperationContext('prefix/foo', 'theme'),
            false,
            [
                '/templates/prefix/foo.html.twig' => '@Contao_Global/prefix/foo.html.twig',
                '/vendor/contao/core-bundle/contao/templates/prefix/foo.html.twig' => '@Contao_ContaoCoreBundle/prefix/foo.html.twig',
            ],
        ];

        yield 'matching variant identifier in theme context' => [
            static::getOperationContext('prefix/foo/my_variant', 'theme'),
            false,
            [
                '/templates/prefix/foo/my_variant.html.twig' => '@Contao_Global/prefix/foo/my_variant.html.twig',
                '/vendor/contao/core-bundle/contao/templates/prefix/foo/my_variant.html.twig' => '@Contao_ContaoCoreBundle/prefix/foo/my_variant.html.twig',
            ],
        ];

        yield 'no root template' => [
            static::getOperationContext('bar/foo', 'theme'),
            false,
            [
                '/vendor/contao/core-bundle/contao/templates/bar/foo.html.twig' => '@Contao_ContaoCoreBundle/bar/foo.html.twig',
            ],
        ];
    }

    public function testDeleteVariantTemplateMigratesDatabaseToEmptyValue(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'some_table',
                ['some_template_field' => ''],
                ['some_template_field' => 'prefix/foo/my_variant'],
            )
        ;

        $operation = $this->getDeleteVariantOperation($connection);

        $operation->execute(
            new Request(request: ['confirm_delete' => true]),
            static::getOperationContext('prefix/foo/my_variant'),
        );
    }

    public function testDeleteVariantTemplateMigratesDatabaseToDefaultValue(): void
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->expects($this->once())
            ->method('update')
            ->with(
                'some_table',
                ['some_template_field' => 'prefix/foo'],
                ['some_template_field' => 'prefix/foo/my_variant'],
            )
        ;

        $operation = $this->getDeleteVariantOperation($connection, null, true);

        $operation->execute(
            new Request(request: ['confirm_delete' => true]),
            static::getOperationContext('prefix/foo/my_variant'),
        );
    }

    private function getDeleteVariantOperation(Connection|null $connection = null, ContaoFilesystemLoader|null $loader = null, bool $shouldSetDatabaseValueToDefaultWhenMigrating = false): AbstractDeleteVariantOperation
    {
        $operation = new class($shouldSetDatabaseValueToDefaultWhenMigrating) extends AbstractDeleteVariantOperation {
            public function __construct(private readonly bool $shouldSetDatabaseValueToDefaultWhenMigrating)
            {
            }

            protected function getPrefix(): string
            {
                return 'prefix';
            }

            protected function getDatabaseReferencesThatShouldBeMigrated(): array
            {
                return ['some_table.some_template_field'];
            }

            protected function shouldSetDatabaseValueToDefaultWhenMigrating(): bool
            {
                return $this->shouldSetDatabaseValueToDefaultWhenMigrating;
            }
        };

        $container = $this->getContainer($loader);
        $container->set('database_connection', $connection);

        $operation->setContainer($container);
        $operation->setName('rename_prefix_variant');

        return $operation;
    }
}
