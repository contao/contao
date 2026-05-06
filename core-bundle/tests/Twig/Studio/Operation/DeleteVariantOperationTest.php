<?php

declare(strict_types=1);

namespace Contao\CoreBundle\Tests\Twig\Studio\Operation;

use Contao\CoreBundle\Twig\Studio\Operation\AbstractDeleteVariantOperation;
use Contao\CoreBundle\Twig\Studio\Operation\OperationContext;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

class DeleteVariantOperationTest extends AbstractOperationTestCase
{
    #[DataProvider('provideContextsAndIfAllowedToExecute')]
    public function testCanExecute(OperationContext $context, bool $canExecute): void
    {
        $this->assertSame(
            $canExecute,
            $this->getDeleteVariantOperation()->canExecute($context),
        );
    }

    public static function provideContextsAndIfAllowedToExecute(): iterable
    {
        yield 'arbitrary identifier' => [
            static::getOperationContext('bar/foo'),
            false,
        ];

        yield 'identifier matching the prefix' => [
            static::getOperationContext('prefix/foo'),
            false,
        ];

        yield 'matching variant identifier' => [
            static::getOperationContext('prefix/foo/my_variant'),
            true,
        ];

        yield 'matching nested variant identifier' => [
            static::getOperationContext('prefix/foo/bar/my_variant'),
            true,
        ];

        yield 'arbitrary identifier in theme context' => [
            static::getOperationContext('bar/foo', 'theme'),
            false,
        ];

        yield 'identifier matching the prefix in theme context' => [
            static::getOperationContext('prefix/foo', 'theme'),
            false,
        ];

        yield 'matching variant identifier in theme context' => [
            static::getOperationContext('prefix/foo/my_variant', 'theme'),
            false,
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

        $operation = $this->getDeleteVariantOperation($connection, true);

        $operation->execute(
            new Request(request: ['confirm_delete' => true]),
            static::getOperationContext('prefix/foo/my_variant'),
        );
    }

    private function getDeleteVariantOperation(Connection|null $connection = null, bool $shouldSetDatabaseValueToDefaultWhenMigrating = false): AbstractDeleteVariantOperation
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

        $container = $this->getContainer();
        $container->set('database_connection', $connection);

        $operation->setContainer($container);
        $operation->setName('rename_prefix_variant');

        return $operation;
    }
}
